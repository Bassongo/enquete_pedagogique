* =============================================================
*  Poverty analysis and transfer simulations
*  EHCVM data - fully documented in English
* =============================================================
* Output directories
global graph_dir "graphes"
global results_dir "results"
global bases_dir "bases"
capture mkdir "${graph_dir}"
capture mkdir "${results_dir}"
capture mkdir "${bases_dir}"
* 1) Definition of the double_gini program
*     (double-sum implementation without packages)
* =============================================================
capture program drop double_gini
program define double_gini, rclass
    version 15.1
    syntax varname [if]
    marksample touse
    preserve
        keep if `touse'

        * Ensure the individual weight exists
        capture confirm variable weight_indiv
        if _rc {
            di as err "ERROR: variable weight_indiv introuvable."
            exit 198
        }

        * Temporary variables
        tempvar wt total_wt total_w s_i p_i S_i S_i1 part

        * 1. Weighted consumption
        gen double `wt' = `varlist' * weight_indiv

        * 2. Totals
        quietly sum `wt'
        scalar total_wt = r(sum)
        quietly sum weight_indiv
        scalar total_w  = r(sum)

        * 3. Individual shares
        gen double `s_i' = `wt'       / total_wt
        gen double `p_i' = weight_indiv / total_w

        * 4. Sort in ascending order
        sort `varlist'

        * 5. Cumulative shares
        gen double `S_i'  = sum(`s_i')
        gen double `S_i1' = `S_i'[_n-1] if _n>1
        replace `S_i1'    = 0         if _n==1

        * 6. Contribution to Gini
        gen double `part' = (`S_i' + `S_i1') * `p_i'

        * 7. Sum of contributions -> index
        quietly sum `part'
        scalar G = 1 - r(sum)

        * 8. Display and return
        display as result "double_gini(`varlist') = " %9.6f G
        return scalar gini = G

    restore
end

* =============================================================
* 1. Baseline analysis (2018 data)
*    - load the original dataset
*    - create standard indicators
* =============================================================
* Location of the online datasets
global repo "https://raw.githubusercontent.com/Bassongo/poverty-analysis/main"
global PIB 18619.5    // GDP in billions of CFA francs
use "${repo}/data/ehcvm_welfare_SEN2018.dta", clear
    rename hhweight weight          // household weight
    rename pcexp    pcexp_orig      // initial expenditure
    * Per-capita expenditure adjusted for deflators
    gen double pcexp = dtot /(hhsize * def_spa * def_temp)
    rename pcexp    cons_pc
    rename zref     poverty_line
    rename milieu   area
    rename hhsize   size

    * Household size used as individual weight
    gen weight_indiv = weight*size
    * FGT indicators (poverty headcount, gap, squared gap)
    gen pauvre  = (cons_pc < poverty_line)
    gen gap     = pauvre*(poverty_line-cons_pc)/poverty_line
    gen sq_gap  = gap^2

    * ---- Overall measures ----
    summ pauvre [aw=weight_indiv]
    scalar p0 = 100*r(mean)
    summ gap [aw=weight_indiv]
    scalar p1 = 100*r(mean)
    summ sq_gap [aw=weight_indiv]
    scalar p2 = 100*r(mean)

    * ---- Urban/rural breakdown ----
    foreach a in 1 2 {
        summ pauvre [aw=weight_indiv] if area==`a'
        scalar p0_`a' = 100*r(mean)
        summ gap [aw=weight_indiv] if area==`a'
        scalar p1_`a' = 100*r(mean)
        summ sq_gap [aw=weight_indiv] if area==`a'
        scalar p2_`a' = 100*r(mean)
    }

    * ---- Gini index (custom function) ----
    double_gini cons_pc
    scalar gini = 100*r(gini)
    foreach x in 1 2 {
        double_gini cons_pc if area==`x'
        scalar gini_`x' = 100*r(gini)
    }

    * ---- Summary table ----
    tempname table
    postfile `table' str10 milieu P0 P1 P2 Gini using "${bases_dir}/fgt_gini_resume.dta", replace
    post `table' ("Global") (p0) (p1) (p2) (gini)
    post `table' ("Urban") (p0_1) (p1_1) (p2_1) (gini_1)
    post `table' ("Rural")  (p0_2) (p1_2) (p2_2) (gini_2)
    postclose `table'
    preserve
    use "${bases_dir}/fgt_gini_resume.dta", clear
    * Create the Excel workbook with the baseline results
    export excel using "${results_dir}/results.xlsx", sheet("Baseline") firstrow(variables) replace
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
    graph export "${graph_dir}/lorenz_global.png", replace

    cap drop p_urb q_urb
    glcurve cons_pc [aw=weight_indiv] if area==1, lorenz pvar(p_urb) glvar(q_urb) replace
    twoway (line q_urb p_urb, sort lcolor(blue)) ///
           (function y=x, range(0 1) lpattern(dash)), ///
           title("Lorenz Curve – Urban") ///
           xtitle("Cumulative population") ytitle("Cumulative consumption") ///
           legend(off)
    graph export "${graph_dir}/lorenz_urbain.png", replace

    cap drop p_rur q_rur
    glcurve cons_pc [aw=weight_indiv] if area==2, lorenz pvar(p_rur) glvar(q_rur) replace
    twoway (line q_rur p_rur, sort lcolor(blue)) ///
           (function y=x, range(0 1) lpattern(dash)), ///
           title("Lorenz Curve – Rural") ///
           xtitle("Cumulative population") ytitle("Cumulative consumption") ///
           legend(off)
    graph export "${graph_dir}/lorenz_rural.png", replace


* =============================================================
* 2. Update the dataset (2018 -> 2023)
*    Apply inflation rates and save
* =============================================================
replace cons_pc    = cons_pc*1.259      // adjust expenditure for inflation
replace weight     = weight*1.153       // population growth adjustment
foreach infl in 0.005 0.010 0.025 0.022 0.097 0.059 {
    replace poverty_line = poverty_line*(1+`infl')
}
* Restore original names before saving
rename cons_pc   pcexp
rename weight    hhweight
rename poverty_line zref
rename area      milieu
    rename size      hhsize
    drop weight_indiv pauvre gap sq_gap
    save "${bases_dir}/base2023.dta", replace

* =============================================================
* 3. Analysis on the updated 2023 dataset
* =============================================================
use "${bases_dir}/base2023.dta", clear
    rename hhweight weight       // updated weight
    rename pcexp    cons_pc      // updated expenditure
    rename zref     poverty_line
    rename milieu   area
    rename hhsize   size
    gen weight_indiv = weight*size
    gen pauvre  = (cons_pc < poverty_line)
    gen gap     = pauvre*(poverty_line-cons_pc)/poverty_line
    gen sq_gap  = gap^2
    summ pauvre [aw=weight_indiv]
    scalar p0a = 100*r(mean)
    summ gap [aw=weight_indiv]
    scalar p1a = 100*r(mean)
    summ sq_gap [aw=weight_indiv]
    scalar p2a = 100*r(mean)
    foreach a in 1 2 {
        summ pauvre [aw=weight_indiv] if area==`a'
        scalar p0a_`a' = 100*r(mean)
        summ gap [aw=weight_indiv] if area==`a'
        scalar p1a_`a' = 100*r(mean)
        summ sq_gap [aw=weight_indiv] if area==`a'
        scalar p2a_`a' = 100*r(mean)
    }
    * Gini index recalculated after update
    double_gini cons_pc
    scalar ginia = 100*r(gini)
    foreach x in 1 2 {
        double_gini cons_pc if area==`x'
        scalar ginia_`x' = 100*r(gini)
    }
    tempname tablea
    postfile `tablea' str10 milieu P0 P1 P2 Gini using "${bases_dir}/post_aging.dta", replace
    post `tablea' ("Global") (p0a) (p1a) (p2a) (ginia)
    post `tablea' ("Urban") (p0a_1) (p1a_1) (p2a_1) (ginia_1)
    post `tablea' ("Rural")  (p0a_2) (p1a_2) (p2a_2) (ginia_2)
    postclose `tablea'
    use "${bases_dir}/post_aging.dta", clear
    * Add 2023 results to the Excel workbook
    export excel using "${results_dir}/results.xlsx", sheet("Aging") firstrow(variables) sheetmodify

* =============================================================
* 4. Prepare the scenarios dataset
*    (identify potential beneficiaries)
* =============================================================
use "${repo}/data/copie_ehcvm_individu_SEN2018.dta", clear
* Indicator variables used for targeting
gen bebe     = age<=2
gen under5   = age<=5
gen under18  = age<18
gen elder    = age>65
gen handicap = handit==1
keep hhid bebe under18 under5 handicap elder
save "${bases_dir}/scenos_tmp.dta", replace
merge m:1 hhid using "${bases_dir}/base2023.dta"
keep if inlist(_merge,2,3)
replace bebe     = 0 if missing(bebe)
replace under5   = 0 if missing(under5)
replace under18  = 0 if missing(under18)
replace elder    = 0 if missing(elder)
replace handicap = 0 if missing(handicap)
drop _merge
* Summarize at the household level and merge demographics
collapse (max) bebe under5 under18 elder handicap (first) pcexp zref hhweight hhsize milieu def_spa def_temp, by(hhid)
label define lbl_area 1 "Urban" 2 "Rural"
label values milieu lbl_area
save "${bases_dir}/scenarios.dta", replace

* =============================================================
* 5. Program: calc_ind
*    Computes FGT indicators and the Gini index
*    for a given consumption variable
* =============================================================
capture program drop calc_ind
program define calc_ind
    args var prefix
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
        double_gini `var' `cond'
        scalar Gini`prefix'`suf'=r(gini)*100
        drop pauvre`prefix'`suf' gap`prefix'`suf' sq_gap`prefix'`suf'
    }
end

* =============================================================
* 6. Program: run_sce
*    Runs a cash transfer scenario
*    and exports the results
* =============================================================
capture program drop run_sce
program define run_sce
    args name condition
    use "${bases_dir}/scenarios.dta", clear
    rename (hhweight pcexp zref milieu hhsize) (weight cons_pc poverty_line area size)
    gen weight_indiv=weight*size
    gen cons_pre=cons_pc
    calc_ind cons_pre _pre
    gen transfert=0
    replace transfert=100000 `condition'   // transfer amount
    * household size already renamed as size
    replace cons_pc=cons_pre + (transfert/(size * def_spa * def_temp))
    calc_ind cons_pc _post
    gen cost_hh=transfert*weight
    gen benef_hh=(transfert>0)
    gen w_benef=benef_hh*weight
    quietly summ cost_hh
    scalar Cost_total=r(sum)
    quietly summ w_benef
    scalar N_benef=r(sum)
    drop benef_hh w_benef
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
    * Display with three decimals to capture small changes
    matlist results, format(%9.3f)
    * Export results and cost into a single workbook
    local out = "${results_dir}/results.xlsx"
    putexcel set "`out'", sheet("`name'") modify
    putexcel A1=matrix(results), names
    matrix cost = (Cost_total, Cost_PIB, N_benef)
    matrix colnames cost = Cost_FCFA Pct_GDP Benef_HH
    * Append cost just below the results
    putexcel A11=matrix(cost), names
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
        graph export "${graph_dir}/lorenz_`fil'_`name'.png", replace
    }
    save "${bases_dir}/scenario`name'_analyse.dta", replace
end

* =============================================================
* 7. Run the different allocation scenarios
* =============================================================
local names "1_universel 2_rural 3_bebe 4_bebe_rural 5_under5 6_under18 7_elderly 8_handicap"   // scenario labels
local cond1 ""
local cond2 "if area==2"
local cond3 "if bebe==1"
local cond4 "if bebe==1 & area==2"
local cond5 "if under5==1"
local cond6 "if under18==1"
local cond7 "if elder==1"
local cond8 "if handicap==1"
forvalues i=1/8 {
    local nm : word `i' of `names'
    * Running scenario number `i'
    run_sce "`nm'" "`cond`i''"
}

* =============================================================
* 8. Tests for the double_gini program
* =============================================================
* -- TEST 1 : Gini sur la base 2023
use "${bases_dir}/base2023.dta", clear
gen weight_indiv = hhweight * hhsize
double_gini pcexp
display "→ Gini(base2023) = " %6.4f (r(gini)*100) "%"

* -- TEST 2 : Gini avant transfert universel
use "${bases_dir}/scenario1_universel_analyse.dta", clear
double_gini cons_pre
display "→ Gini(pré-transfert) = " %6.4f (r(gini)*100) "%"

display "\nLes graphes ont été sauvegardés dans le dossier '${graph_dir}'."
display "Le fichier Excel se trouve dans le dossier '${results_dir}'."
display "Les bases de données sont stockées dans le dossier '${bases_dir}'."
