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
    matrix results = ( ///
        P0_pre,  P1_pre,  P2_pre,  Gini_pre  \  ///
        P0_post, P1_post, P2_post, Gini_post \  ///
        EffP0,   EffP1,   EffP2,   EffGini   \  ///
        P0_pre_urb,  P1_pre_urb,  P2_pre_urb,  Gini_pre_urb \  ///
        P0_post_urb, P1_post_urb, P2_post_urb, Gini_post_urb \  ///
        EffP0_urb,   EffP1_urb,   EffP2_urb,   EffGini_urb \  ///
        P0_pre_rur,  P1_pre_rur,  P2_pre_rur,  Gini_pre_rur \  ///
        P0_post_rur, P1_post_rur, P2_post_rur, Gini_post_rur \  ///
        EffP0_rur,   EffP1_rur,   EffP2_rur,   EffGini_rur   )
    matrix rownames results = Avant_Global Après_Global Efficacité_Global ///
                             Avant_Urbain Après_Urbain Efficacité_Urbain ///
                             Avant_Rural Après_Rural Efficacité_Rural
    matrix colnames results = P0 P1 P2 Gini
    matlist results, format(%9.2f)
    foreach s in "" "_urb" "_rur" {
        local cond = cond("`s'"=="","",cond("`s'"=="_urb","if area==1","if area==2"))
        glcurve cons_pre [aw=weight_indiv] `cond', lorenz pvar(p`s'_pre) glvar(q`s'_pre) replace
        glcurve cons_pc  [aw=weight_indiv] `cond', lorenz pvar(p`s'_post) glvar(q`s'_post) replace
        local lab = cond("`s'"=="","Global",cond("`s'"=="_urb","Urbain","Rural"))
        twoway (line q`s'_pre  p`s'_pre,  sort lpattern(solid)) \
               (line q`s'_post p`s'_post, sort lpattern(dash)) \
               (function y=x, range(0 1) lpattern(dot)), \
               title("Courbe de Lorenz – `lab' (Scénario `name')") \
               legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°")) \
               xtitle("Population cumulée") ytitle("Consommation cumulée")
        local fil = lower(subinstr("`lab'"," ","_",.))
        graph export "lorenz_`fil'_`name'.png", replace
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
    run_sce "`nm'" "`cond`i''"
}
