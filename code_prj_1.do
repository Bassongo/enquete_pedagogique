* ==== Initial analysis 2018 ====
global PIB 18619.5    // GDP in billions of CFA francs
use "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\ehcvm_welfare_SEN2018.dta", clear
    rename hhweight weight
    rename pcexp    cons_pc
    rename zref     poverty_line
    rename milieu   area
    rename hhsize   size

    gen weight_indiv = weight*size
    gen pauvre  = (cons_pc < poverty_line)
    gen gap     = pauvre*(poverty_line-cons_pc)/poverty_line
    gen sq_gap  = gap^2

    * Overall FGT
    summ pauvre [aw=weight_indiv]
    local p0 = 100*r(mean)
    summ gap [aw=weight_indiv]
    local p1 = 100*r(mean)
    summ sq_gap [aw=weight_indiv]
    local p2 = 100*r(mean)

    * FGT by area
    foreach a in 1 2 {
        summ pauvre [aw=weight_indiv] if area==`a'
        local p0_`a' = 100*r(mean)
        summ gap [aw=weight_indiv] if area==`a'
        local p1_`a' = 100*r(mean)
        summ sq_gap [aw=weight_indiv] if area==`a'
        local p2_`a' = 100*r(mean)
    }

    * Gini index
    cap which ineqdeco
    if _rc!=0 ssc install ineqdeco
    ineqdeco cons_pc [aw=weight_indiv]
    local gini = 100*r(gini)
    foreach x in 1 2 {
        ineqdeco cons_pc [aw=weight_indiv] if area==`x'
        local gini_`x' = 100*r(gini)
    }

    * Summary table
    tempname table
    postfile `table' str10 milieu P0 P1 P2 Gini using fgt_gini_resume.dta, replace
    post `table' ("Global") (`p0') (`p1') (`p2') (`gini')
    post `table' ("Urban") (`p0_1') (`p1_1') (`p2_1') (`gini_1')
    post `table' ("Rural")  (`p0_2') (`p1_2') (`p2_2') (`gini_2')
    postclose `table'
    preserve
    use fgt_gini_resume.dta, clear
    list, clean
    restore

    * Lorenz curves
    cap drop p_global q_global
    glcurve cons_pc [aw=weight_indiv], lorenz pvar(p_global) glvar(q_global) replace
    twoway (line q_global p_global, sort lcolor(blue)) ///
           (function y=x, range(0 1) lpattern(dash)), ///
           title("Lorenz Curve – Global") ///
           xtitle("Cumulative population") ytitle("Cumulative consumption") ///
           legend(off)
    graph export lorenz_global.png, replace

    cap drop p_urb q_urb
    glcurve cons_pc [aw=weight_indiv] if area==1, lorenz pvar(p_urb) glvar(q_urb) replace
    twoway (line q_urb p_urb, sort lcolor(blue)) ///
           (function y=x, range(0 1) lpattern(dash)), ///
           title("Lorenz Curve – Urban") ///
           xtitle("Cumulative population") ytitle("Cumulative consumption") ///
           legend(off)
    graph export lorenz_urbain.png, replace

    cap drop p_rur q_rur
    glcurve cons_pc [aw=weight_indiv] if area==2, lorenz pvar(p_rur) glvar(q_rur) replace
    twoway (line q_rur p_rur, sort lcolor(blue)) ///
           (function y=x, range(0 1) lpattern(dash)), ///
           title("Lorenz Curve – Rural") ///
           xtitle("Cumulative population") ytitle("Cumulative consumption") ///
           legend(off)
    graph export lorenz_rural.png, replace


* ==== Update base 2018 to 2023 ====
* Variables were renamed earlier. The update is applied to the new names
* then the original names are restored before saving.
replace cons_pc    = cons_pc*1.248
replace weight     = weight*1.153
foreach infl in 0.005 0.010 0.025 0.022 0.097 {
    replace poverty_line = poverty_line*(1+`infl')
}
* Restore original names for further processing
rename cons_pc   pcexp
rename weight    hhweight
rename poverty_line zref
rename area      milieu
rename size      hhsize
save "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\base2023.dta", replace

* ==== Preparing scenarios dataset ====
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
label define lbl_area 1 "Urban" 2 "Rural"
label values milieu lbl_area
save scenarios.dta, replace

* ==== Indicator program ====
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

* ==== Scenario program ====
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
    quietly summ cost_hh
    scalar Cost_total=r(sum)
    * Display the cost without scientific notation
    di "Total transfer cost (FCFA): " %32.30f Cost_total
    * Total program cost in billions for efficiency calculations
    scalar Cost_billion=Cost_total/1e9
    * Share of cost in GDP
    scalar Cost_PIB = (Cost_billion/$PIB)*100
    foreach suf in "" "_urb" "_rur" {
        local tag = cond("`suf'"=="","_glob","`suf'")
        scalar Eff_P0`tag' = (P0_pre`suf' - P0_post`suf')/Cost_billion
        scalar Eff_P1`tag' = (P1_pre`suf' - P1_post`suf')/Cost_billion
        scalar Eff_P2`tag' = (P2_pre`suf' - P2_post`suf')/Cost_billion
        scalar Eff_Gini`tag' = (Gini_pre`suf' - Gini_post`suf')/Cost_billion
    }
    matrix results = ( ///
        P0_pre,  P1_pre,  P2_pre,  Gini_pre  \  ///
        P0_post, P1_post, P2_post, Gini_post \  ///
        Eff_P0_glob,   Eff_P1_glob,   Eff_P2_glob,   Eff_Gini_glob   \  ///
        P0_pre_urb,  P1_pre_urb,  P2_pre_urb,  Gini_pre_urb \  ///
        P0_post_urb, P1_post_urb, P2_post_urb, Gini_post_urb \  ///
        Eff_P0_urb,   Eff_P1_urb,   Eff_P2_urb,   Eff_Gini_urb \  ///
        P0_pre_rur,  P1_pre_rur,  P2_pre_rur,  Gini_pre_rur \  ///
        P0_post_rur, P1_post_rur, P2_post_rur, Gini_post_rur \  ///
        Eff_P0_rur,   Eff_P1_rur,   Eff_P2_rur,   Eff_Gini_rur   )
    matrix rownames results = Before_Global After_Global Efficiency_Global ///
                             Before_Urban After_Urban Efficiency_Urban ///
                             Before_Rural After_Rural Efficiency_Rural
    matrix colnames results = P0 P1 P2 Gini
    * Display with three decimals to see small changes
    matlist results, format(%9.3f)
    * Export results and cost to Excel
    local out = "scenario`name'.xlsx"
    putexcel set "`out'", sheet("Summary") replace
    putexcel A1=matrix(results), names
    matrix cost = (Cost_total, Cost_PIB)
    matrix colnames cost = Cost_FCFA Pct_GDP
    * Add cost in a second sheet without overwriting the summary
    putexcel set "`out'", sheet("Cost") modify
    putexcel A1=matrix(cost), names
    foreach s in "" "_urb" "_rur" {
        local cond = cond("`s'"=="","",cond("`s'"=="_urb","if area==1","if area==2"))
        glcurve cons_pre [aw=weight_indiv] `cond', lorenz pvar(p`s'_pre) glvar(q`s'_pre) replace
        glcurve cons_pc  [aw=weight_indiv] `cond', lorenz pvar(p`s'_post) glvar(q`s'_post) replace
        local lab = cond("`s'"=="","Global",cond("`s'"=="_urb","Urban","Rural"))
        twoway (line q`s'_pre  p`s'_pre,  sort lpattern(solid)) ///
               (line q`s'_post p`s'_post, sort lpattern(dash)) ///
               (function y=x, range(0 1) lpattern(dot)), ///
               title("Lorenz Curve – `lab' (Scenario `name')") ///
               legend(order(1 "Pre-transfer" 2 "Post-transfer" 3 "45°")) ///
               xtitle("Cumulative population") ytitle("Cumulative consumption")
        local fil = lower(subinstr("`lab'"," ","_",.))
        graph export "lorenz_`fil'_`name'.png", replace
    }
    save "scenario`name'_analyse.dta", replace
end

* ==== Run scenarios ====
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
