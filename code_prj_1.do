* ==== Mise à jour base 2018 vers 2023 ====
use "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\ehcvm_welfare_SEN2018.dta", clear
replace pcexp    = pcexp*1.248
replace hhweight = hhweight*1.153
foreach infl in 0.005 0.010 0.025 0.022 0.097 {
    replace zref = zref*(1+`infl')
}
save "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\base2023.dta", replace

* ==== Préparation base scénarios ====
use "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\copie_ehcvm_individu_SEN2018.dta", clear
gen bebe     = age<=2
gen under5   = age<=5
gen under18  = age<18
gen elder    = age>65
gen handicap = handit==1
keep hhid bebe under18 under5 handicap elder
save scenos_tmp, replace
merge m:1 hhid using "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\base2023.dta"
drop _merge
collapse (max) bebe under5 under18 elder handicap (first) pcexp zref hhweight hhsize milieu, by(hhid)
label define lbl_area 1 "Urbain" 2 "Rural"
label values milieu lbl_area
save scenarios.dta, replace

* ==== Programme indicateurs ==== 
capture program drop calc_ind
program define calc_ind
    args var prefix
    cap which ineqdeco
    if _rc!=0 ssc install ineqdeco
    foreach s in 0 1 2 {
        local suf=cond(`s'==0, "", cond(`s'==1, "_urb", "_rur"))
        local cond=cond(`s'==0, "", cond(`s'==1, "if area==1", "if area==2"))
        gen pauvre`prefix'`suf' = (`var'<poverty_line)
        gen gap`prefix'`suf'    = pauvre`prefix'`suf'*(poverty_line-`var')/poverty_line
        gen sq_gap`prefix'`suf' = gap`prefix'`suf'^2
        summ pauvre`prefix'`suf' [aw=weight_indiv] `cond'
        scalar P0`prefix'`suf'=r(mean)*100
        summ gap`prefix'`suf' [aw=weight_indiv] `cond'
        scalar P1`prefix'`suf'=r(mean)*100
        summ sq_gap`prefix'`suf' [aw=weight_indiv] `cond'
        scalar P2`prefix'`suf'=r(mean)*100
        ineqdeco `var' [aw=weight_indiv] `cond'
        scalar Gini`prefix'`suf'=r(gini)*100
        drop pauvre`prefix'`suf' gap`prefix'`suf' sq_gap`prefix'`suf'
    }
end

* ==== Programme scénario ====
capture program drop run_sce
program define run_sce
    args name condition
    use scenarios.dta, clear
    rename (hhweight pcexp zref milieu hhsize) (weight cons_pc poverty_line area size)
    gen weight_indiv=weight*size
    gen cons_pre=cons_pc
    calc_ind cons_pre _pre
    gen transfert=0
    replace transfert=100000 `condition'
    replace cons_pc=cons_pre+(transfert/size)
    calc_ind cons_pc _post
    gen cost_hh=transfert*weight
    summ cost_hh
    scalar cost=r(sum)/1e10
    foreach s in "" "_urb" "_rur" {
        scalar EffP0`s' = (P0_pre`s' - P0_post`s')/cost
        scalar EffP1`s' = (P1_pre`s' - P1_post`s')/cost
        scalar EffP2`s' = (P2_pre`s' - P2_post`s')/cost
        scalar EffGini`s' = (Gini_pre`s' - Gini_post`s')/cost
    }
    save "scenario`name'_analyse.dta", replace
end

* ==== Exécution des scénarios ====
local names "1_universel 2_rural 3_bebe 4_bebe_rural 5_bebe_rural2 6_under18 7_elderly 8_handicap"
local cond1 ""
local cond2 "if area==2"
local cond3 "if bebe==1"
local cond4 "if bebe==1 & area==2"
local cond5 "if bebe==1 & area==2"
local cond6 "if under18==1"
local cond7 "if elder==1"
local cond8 "if handicap==1"
forvalues i=1/8 {
    local nm : word `i' of `names'
    run_sce "`nm'" ``cond`i''
}
