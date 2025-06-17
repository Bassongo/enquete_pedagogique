**********************************************************************      La situation après avoir ager la base EN 2023                 *
*********************************************************************
*chargement de la base2018
use "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\ehcvm_welfare_SEN2018.dta", clear

* ---------------------------------------------------------
* Aging pas à pas vers 2023 en appliquant les taux annuels
* ---------------------------------------------------------
replace pcexp= pcexp*1.248
replace hhweight= hhweight*1.153
* 3. Adapter le seuil de pauvreté (zref) au fil de l'inflation cumulée
replace zref = zref * (1 + 0.005)           // vers 2019
replace zref = zref * (1 + 0.010)           // vers 2020
replace zref = zref * (1 + 0.025)           // vers 2021
replace zref = zref * (1 + 0.022)           // vers 2022
replace zref = zref * (1 + 0.097)           // vers 2023
* 4. Sauvegarder la base mise à jour pour 2023
capture save "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\base2023.dta", replace
**********************************************************************                  Analyse de la situation en 2023
*********************************************************************
use "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\base2023.dta"

*=== 2. Renommer et préparer les variables ===*
rename hhweight weight
rename pcexp cons_pc
rename zref poverty_line
rename milieu area
rename hhsize size

*=== 3. Créer le poids individuel ===*
gen weight_indiv = weight * size

*=== 4. Statut de pauvreté (par tête) ===*
gen pauvre = (cons_pc < poverty_line)
gen gap = pauvre * (poverty_line - cons_pc) / poverty_line
gen sq_gap = gap^2

*=== 5. Calcul FGT globaux (en %) ===*
summ pauvre [aw=weight_indiv]
display "P0 (Incidence) = " %4.2f 100*r(mean) " %"

summ gap [aw=weight_indiv]
display "P1 (Profondeur) = " %4.2f 100*r(mean) " %"

summ sq_gap [aw=weight_indiv]
display "P2 (Sévérité) = " %4.2f 100*r(mean) " %"

*=== 6. FGT par milieu (urbain/rural) ===*
foreach a in 1 2 {
    display "------ Résultats pour zone : `a' ------"
    
    summ pauvre [aw=weight_indiv] if area==`a'
    display "P0 = " %4.2f 100*r(mean) " %"

    summ gap [aw=weight_indiv] if area==`a'
    display "P1 = " %4.2f 100*r(mean) " %"

    summ sq_gap [aw=weight_indiv] if area==`a'
    display "P2 = " %4.2f 100*r(mean) " %"
}

**** calcule de l'indice de GINI *******
* installation du package  ineqdeco
cap which ineqdeco
if _rc != 0 {
    ssc install ineqdeco
}

* Gini global
ineqdeco cons_pc [aw=weight_indiv]
display "Gini (Global) = " %4.2f 100*r(gini) " %"

* Gini par milieu
foreach x in 1 2 {
    ineqdeco cons_pc [aw=weight_indiv] if area==`x'
    local g = 100 * r(gini)
    display "Gini (zone `x') = " %4.2f `g' " %"
}

********** tableau resumant les FGT et indice de GINI ***************
*=== Initialisation ===*
preserve 
tempname table
postfile `table' str10 milieu P0 P1 P2 Gini using fgt_gini_resume.dta, replace

*=== Vérification de ineqdeco ===*
cap which ineqdeco
if _rc != 0 ssc install ineqdeco

*=== GLOBAL ===*
summ pauvre [aw=weight_indiv]
local p0 = 100 * r(mean)
summ gap [aw=weight_indiv]
local p1 = 100 * r(mean)
summ sq_gap [aw=weight_indiv]
local p2 = 100 * r(mean)
ineqdeco cons_pc [aw=weight_indiv]
local gini = 100 * r(gini)
post `table' ("Global") (`p0') (`p1') (`p2') (`gini')

*=== URBAIN ET RURAL ===*
foreach x in 1 2 {
    summ pauvre [aw=weight_indiv] if area==`x'
    local p0 = 100 * r(mean)
    summ gap [aw=weight_indiv] if area==`x'
    local p1 = 100 * r(mean)
    summ sq_gap [aw=weight_indiv] if area==`x'
    local p2 = 100 * r(mean)
    ineqdeco cons_pc [aw=weight_indiv] if area==`x'
    local gini = 100 * r(gini)

    local label = cond(`x'==1, "Urbain", "Rural")
    post `table' ("`label'") (`p0') (`p1') (`p2') (`gini')
}

postclose `table'
use fgt_gini_resume.dta, clear
list, clean
restore
***********************************************************
**** courbe de Lorenz ****
* Nettoyer d'éventuelles variables existantes
cap drop p_global q_global

* Générer les coordonnées de la courbe de Lorenz
glcurve cons_pc [aw=weight_indiv], lorenz pvar(p_global) glvar(q_global) replace

* Tracer la courbe proprement
twoway (line q_global p_global, sort lcolor(blue)) ///
       (function y=x, range(0 1) lpattern(dash)), ///
       title("Courbe de Lorenz – Global") ///
       xtitle("Population cumulée") ytitle("Consommation cumulée") ///
       legend(off)

* Exporter l'image
graph export lorenz_global.png, replace

* === Urbain ===
cap drop p_urb q_urb
glcurve cons_pc [aw=weight_indiv] if area==1, lorenz pvar(p_urb) glvar(q_urb) replace
twoway (line q_urb p_urb, sort lcolor(blue)) ///
       (function y=x, range(0 1) lpattern(dash)), ///
       title("Courbe de Lorenz – Urbain") ///
       xtitle("Population cumulée") ytitle("Consommation cumulée") ///
       legend(off)
graph export lorenz_urbain.png, replace

* === Rural ===
cap drop p_rur q_rur
glcurve cons_pc [aw=weight_indiv] if area==2, lorenz pvar(p_rur) glvar(q_rur) replace
twoway (line q_rur p_rur, sort lcolor(blue)) ///
       (function y=x, range(0 1) lpattern(dash)), ///
       title("Courbe de Lorenz – Rural") ///
       xtitle("Population cumulée") ytitle("Consommation cumulée") ///
       legend(off)
graph export lorenz_rural.png, replace


*********************************************************************
* Construction de la base des scénario
*********************************************************************
use  "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\copie_ehcvm_individu_SEN2018.dta"
** les bébés
gen bebe=cond(age<=2,1,0) 
** moins de 5 ans 
gen under5=cond(age<=5,1,0)
** moins de 18 ans 
gen under18=cond(age<18,1,0)
** plus de 5 ans 
gen elder=cond(age>65,1,0)
** les handicapes
gen handicap=cond(handit==1,1,0)
** se débarasser de tout ce qui n'est pas utile
keep hhid bebe under18 under5 handicap elder
save scenarios, replace
merge m:1 hhid using base2023.dta
drop _merge 

* Agrégation au niveau des ménages (une ligne = un ménage)
collapse ///
    (max) bebe under5 under18 elder handicap ///   → présence d'un profil ciblé
    (first) pcexp zref hhweight hhsize milieu /// → variables identiques au ménage
, by(hhid)
label define lbl_area 1 "Urbain" 2 "Rural", replace
label values milieu lbl_area

** chargement de la base initiale
save "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenarios.dta", replace

************************************************************
**** SCÉNARIO 1 – Transfert universel
************************************************************

* 0. Charger la base des scénarios
use "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenarios.dta", clear

* 1. Préparer les variables
rename hhweight weight
rename pcexp    cons_pc
rename zref     poverty_line
rename milieu   area
rename hhsize   size
gen weight_indiv = weight * size
gen cons_pre    = cons_pc

************************************************************
**** Analyse PRÉ-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre = (cons_pre < poverty_line)
gen gap    = pauvre * (poverty_line - cons_pre) / poverty_line
gen sq_gap = gap^2

* Global
summ pauvre [aw=weight_indiv]
scalar P0_pre     = r(mean)*100
summ gap [aw=weight_indiv]
scalar P1_pre     = r(mean)*100
summ sq_gap [aw=weight_indiv]
scalar P2_pre     = r(mean)*100
cap which ineqdeco
if _rc != 0 ssc install ineqdeco
ineqdeco cons_pre [aw=weight_indiv]
scalar Gini_pre   = r(gini)*100

* Urbain
summ pauvre [aw=weight_indiv] if area==1
scalar P0_pre_urb = r(mean)*100
summ gap [aw=weight_indiv]    if area==1
scalar P1_pre_urb = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==1
scalar P2_pre_urb = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==1
scalar Gini_pre_urb = r(gini)*100

* Rural
summ pauvre [aw=weight_indiv] if area==2
scalar P0_pre_rur = r(mean)*100
summ gap [aw=weight_indiv]    if area==2
scalar P1_pre_rur = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==2
scalar P2_pre_rur = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==2
scalar Gini_pre_rur = r(gini)*100

************************************************************
**** Transfert universel
************************************************************
gen transfert = 100000
replace cons_pc = cons_pre + (transfert/size)

************************************************************
**** Analyse POST-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre2 = (cons_pc < poverty_line)
gen gap2    = pauvre2 * (poverty_line - cons_pc) / poverty_line
gen sq_gap2 = gap2^2

* Global
summ pauvre2 [aw=weight_indiv]
scalar P0_post     = r(mean)*100
summ gap2 [aw=weight_indiv]
scalar P1_post     = r(mean)*100
summ sq_gap2 [aw=weight_indiv]
scalar P2_post     = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv]
scalar Gini_post   = r(gini)*100

* Urbain
summ pauvre2 [aw=weight_indiv] if area==1
scalar P0_post_urb = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==1
scalar P1_post_urb = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==1
scalar P2_post_urb = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==1
scalar Gini_post_urb = r(gini)*100

* Rural
summ pauvre2 [aw=weight_indiv] if area==2
scalar P0_post_rur = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==2
scalar P1_post_rur = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==2
scalar P2_post_rur = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==2
scalar Gini_post_rur = r(gini)*100

************************************************************
**** Coût et part dans le PIB 2023 (en milliards) ****
************************************************************
gen cost_hh      = transfert * weight
summ cost_hh
scalar Total_cost     = r(sum)
scalar Cost_billion   = Total_cost/1e10

************************************************************
**** Efficacité (par milliard CFA) ****
************************************************************
scalar Eff_P0_glob    = (P0_pre   - P0_post)   / Cost_billion
scalar Eff_P1_glob    = (P1_pre   - P1_post)   / Cost_billion
scalar Eff_P2_glob    = (P2_pre   - P2_post)   / Cost_billion
scalar Eff_Gini_glob  = (Gini_pre - Gini_post) / Cost_billion

scalar Eff_P0_urb     = (P0_pre_urb   - P0_post_urb)   / Cost_billion
scalar Eff_P1_urb     = (P1_pre_urb   - P1_post_urb)   / Cost_billion
scalar Eff_P2_urb     = (P2_pre_urb   - P2_post_urb)   / Cost_billion
scalar Eff_Gini_urb   = (Gini_pre_urb - Gini_post_urb) / Cost_billion

scalar Eff_P0_rur     = (P0_pre_rur   - P0_post_rur)   / Cost_billion
scalar Eff_P1_rur     = (P1_pre_rur   - P1_post_rur)   / Cost_billion
scalar Eff_P2_rur     = (P2_pre_rur   - P2_post_rur)   / Cost_billion
scalar Eff_Gini_rur   = (Gini_pre_rur - Gini_post_rur) / Cost_billion

************************************************************
**** Tableau récapitulatif
************************************************************
matrix results = ( ///
    P0_pre,     P1_pre,     P2_pre,     Gini_pre    \  ///
    P0_post,    P1_post,    P2_post,    Gini_post   \  ///
    Eff_P0_glob,Eff_P1_glob,Eff_P2_glob,Eff_Gini_glob \  ///
    P0_pre_urb, P1_pre_urb, P2_pre_urb, Gini_pre_urb\  ///
    P0_post_urb,P1_post_urb,P2_post_urb,Gini_post_urb\  ///
    Eff_P0_urb, Eff_P1_urb, Eff_P2_urb, Eff_Gini_urb \  ///
    P0_pre_rur, P1_pre_rur, P2_pre_rur, Gini_pre_rur\  ///
    P0_post_rur,P1_post_rur,P2_post_rur,Gini_post_rur\  ///
    Eff_P0_rur, Eff_P1_rur, Eff_P2_rur, Eff_Gini_rur   )

matrix rownames results = Avant_Global Après_Global Efficacité_Global ///
                         Avant_Urbain Après_Urbain Efficacité_Urbain ///
                         Avant_Rural Après_Rural Efficacité_Rural

matrix colnames results = P0 P1 P2 Gini

matlist results, format(%9.2f)

************************************************************
**** Courbes de Lorenz – Global, Urbain et Rural ****
************************************************************

* Générer les coordonnées de Lorenz pour chaque série
cap drop p_pre q_pre p_post q_post ///
         p_urb_pre q_urb_pre p_urb_post q_urb_post ///
         p_rur_pre q_rur_pre p_rur_post q_rur_post

* Global
glcurve cons_pre    [aw=weight_indiv], lorenz pvar(p_pre)    glvar(q_pre)    replace
glcurve cons_pc     [aw=weight_indiv], lorenz pvar(p_post)   glvar(q_post)   replace

* Urbain
glcurve cons_pre    [aw=weight_indiv] if area==1, lorenz pvar(p_urb_pre) glvar(q_urb_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==1, lorenz pvar(p_urb_post)glvar(q_urb_post)replace

* Rural
glcurve cons_pre    [aw=weight_indiv] if area==2, lorenz pvar(p_rur_pre) glvar(q_rur_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==2, lorenz pvar(p_rur_post)glvar(q_rur_post)replace

* Tracé superposé – Global
twoway ///
    (line q_pre  p_pre,    sort lpattern(solid))     ///
    (line q_post p_post,   sort lpattern(dash))      ///
    (function y=x,        range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Global (Scénario 1)")  ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°")) ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_global_s1.png", replace

* Tracé superposé – Urbain
twoway ///
    (line q_urb_pre  p_urb_pre,    sort lpattern(solid))     ///
    (line q_urb_post p_urb_post,   sort lpattern(dash))      ///
    (function y=x,                 range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Urbain (Scénario 1)")  ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°")) ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_urbain_s1.png", replace

* Tracé superposé – Rural
twoway ///
    (line q_rur_pre  p_rur_pre,    sort lpattern(solid))     ///
    (line q_rur_post p_rur_post,   sort lpattern(dash))      ///
    (function y=x,                 range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Rural (Scénario 1)")  ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°")) ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_rural_s1.png", replace

************************************************************
**** Sauvegarde de la base du scénario 1 ****
************************************************************
save "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenario1_universel.dta", replace

************************************************************
**** SCÉNARIO 2 – Transfert universel RURAL uniquement ****
************************************************************

* 0. Charger la base des scénarios
use "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenarios.dta", clear

* 1. Préparer les variables
rename hhweight weight
rename pcexp    cons_pc
rename zref     poverty_line
rename milieu   area
rename hhsize   size
gen weight_indiv = weight * size
gen cons_pre    = cons_pc

************************************************************
**** Analyse PRÉ-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre = (cons_pre < poverty_line)
gen gap    = pauvre * (poverty_line - cons_pre) / poverty_line
gen sq_gap = gap^2

* Global
summ pauvre [aw=weight_indiv]
scalar P0_pre     = r(mean)*100
summ gap [aw=weight_indiv]
scalar P1_pre     = r(mean)*100
summ sq_gap [aw=weight_indiv]
scalar P2_pre     = r(mean)*100
cap which ineqdeco
if _rc != 0 ssc install ineqdeco
ineqdeco cons_pre [aw=weight_indiv]
scalar Gini_pre   = r(gini)*100

* Urbain
summ pauvre [aw=weight_indiv] if area==1
scalar P0_pre_urb = r(mean)*100
summ gap [aw=weight_indiv]    if area==1
scalar P1_pre_urb = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==1
scalar P2_pre_urb = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==1
scalar Gini_pre_urb = r(gini)*100

* Rural
summ pauvre [aw=weight_indiv] if area==2
scalar P0_pre_rur = r(mean)*100
summ gap [aw=weight_indiv]    if area==2
scalar P1_pre_rur = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==2
scalar P2_pre_rur = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==2
scalar Gini_pre_rur = r(gini)*100

************************************************************
**** Transfert universel RURAL
************************************************************
gen transfert = 0
replace transfert = 100000 if area==2
replace cons_pc   = cons_pre + (transfert/size)

************************************************************
**** Analyse POST-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre2 = (cons_pc < poverty_line)
gen gap2    = pauvre2 * (poverty_line - cons_pc) / poverty_line
gen sq_gap2 = gap2^2

* Global
summ pauvre2 [aw=weight_indiv]
scalar P0_post     = r(mean)*100
summ gap2 [aw=weight_indiv]
scalar P1_post     = r(mean)*100
summ sq_gap2 [aw=weight_indiv]
scalar P2_post     = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv]
scalar Gini_post   = r(gini)*100

* Urbain
summ pauvre2 [aw=weight_indiv] if area==1
scalar P0_post_urb = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==1
scalar P1_post_urb = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==1
scalar P2_post_urb = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==1
scalar Gini_post_urb = r(gini)*100

* Rural
summ pauvre2 [aw=weight_indiv] if area==2
scalar P0_post_rur = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==2
scalar P1_post_rur = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==2
scalar P2_post_rur = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==2
scalar Gini_post_rur = r(gini)*100

************************************************************
**** Coût et part dans le PIB 2023 (en dizaines de milliards) ****
************************************************************
gen cost_hh      = transfert * weight
summ cost_hh
scalar Total_cost   = r(sum)
scalar Cost_decab   = Total_cost/1e10

************************************************************
**** Efficacité (par dizaine de milliard CFA) ****
************************************************************
scalar Eff_P0_glob    = (P0_pre   - P0_post)   / Cost_decab
scalar Eff_P1_glob    = (P1_pre   - P1_post)   / Cost_decab
scalar Eff_P2_glob    = (P2_pre   - P2_post)   / Cost_decab
scalar Eff_Gini_glob  = (Gini_pre - Gini_post) / Cost_decab

scalar Eff_P0_urb     = (P0_pre_urb   - P0_post_urb)   / Cost_decab
scalar Eff_P1_urb     = (P1_pre_urb   - P1_post_urb)   / Cost_decab
scalar Eff_P2_urb     = (P2_pre_urb   - P2_post_urb)   / Cost_decab
scalar Eff_Gini_urb   = (Gini_pre_urb - Gini_post_urb) / Cost_decab

scalar Eff_P0_rur     = (P0_pre_rur   - P0_post_rur)   / Cost_decab
scalar Eff_P1_rur     = (P1_pre_rur   - P1_post_rur)   / Cost_decab
scalar Eff_P2_rur     = (P2_pre_rur   - P2_post_rur)   / Cost_decab
scalar Eff_Gini_rur   = (Gini_pre_rur - Gini_post_rur) / Cost_decab

************************************************************
**** Tableau récapitulatif
************************************************************
matrix results = ( ///  
    P0_pre,      P1_pre,      P2_pre,      Gini_pre      \ ///
    P0_post,     P1_post,     P2_post,     Gini_post     \ ///
    Eff_P0_glob, Eff_P1_glob, Eff_P2_glob, Eff_Gini_glob \ ///
    P0_pre_urb,  P1_pre_urb,  P2_pre_urb,  Gini_pre_urb  \ ///
    P0_post_urb, P1_post_urb, P2_post_urb, Gini_post_urb \ ///
    Eff_P0_urb,  Eff_P1_urb,  Eff_P2_urb,  Eff_Gini_urb \ ///
    P0_pre_rur,  P1_pre_rur,  P2_pre_rur,  Gini_pre_rur \ ///
    P0_post_rur, P1_post_rur, P2_post_rur, Gini_post_rur \ ///
    Eff_P0_rur,  Eff_P1_rur,  Eff_P2_rur,  Eff_Gini_rur    ///
)
matrix rownames results = Avant_Global Après_Global Efficacité_Global ///
                         Avant_Urbain Après_Urbain Efficacité_Urbain ///
                         Avant_Rural Après_Rural Efficacité_Rural
matrix colnames results = P0 P1 P2 Gini
matlist results, format(%9.2f)

************************************************************
**** Courbes de Lorenz – Global, Urbain et Rural ****
************************************************************
* Générer les coordonnées
cap drop p_pre q_pre p_post q_post ///
         p_urb_pre q_urb_pre p_urb_post q_urb_post ///
         p_rur_pre q_rur_pre p_rur_post q_rur_post

glcurve cons_pre    [aw=weight_indiv], lorenz pvar(p_pre)    glvar(q_pre)    replace
glcurve cons_pc     [aw=weight_indiv], lorenz pvar(p_post)   glvar(q_post)   replace
glcurve cons_pre    [aw=weight_indiv] if area==1, lorenz pvar(p_urb_pre) glvar(q_urb_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==1, lorenz pvar(p_urb_post)glvar(q_urb_post)replace
glcurve cons_pre    [aw=weight_indiv] if area==2, lorenz pvar(p_rur_pre) glvar(q_rur_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==2, lorenz pvar(p_rur_post)glvar(q_rur_post)replace

* Superposer – Global
twoway ///
    (line q_pre  p_pre,    sort lpattern(solid))     ///
    (line q_post p_post,   sort lpattern(dash))      ///
    (function y=x,        range(0 1) lpattern(dot)), ///
    title("Lorenz – Global (Scénario 2)")  ///
    legend(order(1 "Pré" 2 "Post" 3 "45°")) ///
    xtitle("Pop cumulée") ytitle("Consommation cumulée")
graph export "lorenz_global_s2.png", replace

* Superposer – Urbain
twoway ///
    (line q_urb_pre  p_urb_pre,    sort lpattern(solid))     ///
    (line q_urb_post p_urb_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Lorenz – Urbain (Scénario 2)")  ///
    legend(order(1 "Pré" 2 "Post" 3 "45°")) ///
    xtitle("Pop cumulée") ytitle("Consommation cumulée")
graph export "lorenz_urbain_s2.png", replace

* Superposer – Rural
twoway ///
    (line q_rur_pre  p_rur_pre,    sort lpattern(solid))     ///
    (line q_rur_post p_rur_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Lorenz – Rural (Scénario 2)")  ///
    legend(order(1 "Pré" 2 "Post" 3 "45°")) ///
    xtitle("Pop cumulée") ytitle("Consommation cumulée")
graph export "lorenz_rural_s2.png", replace

************************************************************
**** Sauvegarde de la base du scénario 2 ****
************************************************************
save "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenario2_rural_analyse.dta", replace


************************************************************
**** SCÉNARIO 3 – Transfert aux ménages avec bébé (<2 ans) ****
************************************************************

* 0. Charger la base des scénarios
use "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenarios.dta", clear

* 1. Préparer les variables
rename hhweight weight
rename pcexp    cons_pc
rename zref     poverty_line
rename milieu   area
rename hhsize   size
gen weight_indiv = weight * size
gen cons_pre    = cons_pc

************************************************************
**** Analyse PRÉ-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre  = (cons_pre < poverty_line)
gen gap     = pauvre * (poverty_line - cons_pre) / poverty_line
gen sq_gap  = gap^2

* Global
summ pauvre [aw=weight_indiv]
scalar P0_pre     = r(mean)*100
summ gap [aw=weight_indiv]
scalar P1_pre     = r(mean)*100
summ sq_gap [aw=weight_indiv]
scalar P2_pre     = r(mean)*100
cap which ineqdeco
if _rc != 0 ssc install ineqdeco
ineqdeco cons_pre [aw=weight_indiv]
scalar Gini_pre   = r(gini)*100

* Urbain
summ pauvre [aw=weight_indiv] if area==1
scalar P0_pre_urb = r(mean)*100
summ gap [aw=weight_indiv]    if area==1
scalar P1_pre_urb = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==1
scalar P2_pre_urb = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==1
scalar Gini_pre_urb = r(gini)*100

* Rural
summ pauvre [aw=weight_indiv] if area==2
scalar P0_pre_rur = r(mean)*100
summ gap [aw=weight_indiv]    if area==2
scalar P1_pre_rur = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==2
scalar P2_pre_rur = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==2
scalar Gini_pre_rur = r(gini)*100

************************************************************
**** Transfert ciblé – Bébé <2 ans
************************************************************
gen transfert = 0
replace transfert = 100000 if bebe==1
replace cons_pc   = cons_pre + (transfert/size)

************************************************************
**** Analyse POST-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre2  = (cons_pc < poverty_line)
gen gap2     = pauvre2 * (poverty_line - cons_pc) / poverty_line
gen sq_gap2  = gap2^2

* Global
summ pauvre2 [aw=weight_indiv]
scalar P0_post     = r(mean)*100
summ gap2 [aw=weight_indiv]
scalar P1_post     = r(mean)*100
summ sq_gap2 [aw=weight_indiv]
scalar P2_post     = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv]
scalar Gini_post   = r(gini)*100

* Urbain
summ pauvre2 [aw=weight_indiv] if area==1
scalar P0_post_urb = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==1
scalar P1_post_urb = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==1
scalar P2_post_urb = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==1
scalar Gini_post_urb = r(gini)*100

* Rural
summ pauvre2 [aw=weight_indiv] if area==2
scalar P0_post_rur = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==2
scalar P1_post_rur = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==2
scalar P2_post_rur = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==2
scalar Gini_post_rur = r(gini)*100

************************************************************
**** Coût et part dans le PIB 2023 (en dizaines de milliards) ****
************************************************************
gen cost_hh      = transfert * weight
summ cost_hh
scalar Total_cost = r(sum)
scalar Cost_decab = Total_cost/1e10

************************************************************
**** Efficacité (par dizaine de milliard CFA) ****
************************************************************
scalar Eff_P0_glob    = (P0_pre   - P0_post)   / Cost_decab
scalar Eff_P1_glob    = (P1_pre   - P1_post)   / Cost_decab
scalar Eff_P2_glob    = (P2_pre   - P2_post)   / Cost_decab
scalar Eff_Gini_glob  = (Gini_pre - Gini_post) / Cost_decab

scalar Eff_P0_urb     = (P0_pre_urb   - P0_post_urb)   / Cost_decab
scalar Eff_P1_urb     = (P1_pre_urb   - P1_post_urb)   / Cost_decab
scalar Eff_P2_urb     = (P2_pre_urb   - P2_post_urb)   / Cost_decab
scalar Eff_Gini_urb   = (Gini_pre_urb - Gini_post_urb) / Cost_decab

scalar Eff_P0_rur     = (P0_pre_rur   - P0_post_rur)   / Cost_decab
scalar Eff_P1_rur     = (P1_pre_rur   - P1_post_rur)   / Cost_decab
scalar Eff_P2_rur     = (P2_pre_rur   - P2_post_rur)   / Cost_decab
scalar Eff_Gini_rur   = (Gini_pre_rur - Gini_post_rur) / Cost_decab

************************************************************
**** Tableau récapitulatif
************************************************************
matrix results = ( ///  
    P0_pre,      P1_pre,      P2_pre,      Gini_pre      \ ///
    P0_post,     P1_post,     P2_post,     Gini_post     \ ///
    Eff_P0_glob, Eff_P1_glob, Eff_P2_glob, Eff_Gini_glob \ ///
    P0_pre_urb,  P1_pre_urb,  P2_pre_urb,  Gini_pre_urb  \ ///
    P0_post_urb, P1_post_urb, P2_post_urb, Gini_post_urb \ ///
    Eff_P0_urb,  Eff_P1_urb,  Eff_P2_urb,  Eff_Gini_urb \ ///
    P0_pre_rur,  P1_pre_rur,  P2_pre_rur,  Gini_pre_rur \ ///
    P0_post_rur, P1_post_rur, P2_post_rur, Gini_post_rur \ ///
    Eff_P0_rur,  Eff_P1_rur,  Eff_P2_rur,  Eff_Gini_rur    ///
)
matrix rownames results = Avant_Global Après_Global Efficacité_Global ///
                         Avant_Urbain Après_Urbain Efficacité_Urbain ///
                         Avant_Rural Après_Rural Efficacité_Rural
matrix colnames results = P0 P1 P2 Gini
matlist results, format(%9.2f)

************************************************************
**** Courbes de Lorenz – Global, Urbain et Rural ****
************************************************************
cap drop p_pre q_pre p_post q_post ///
         p_urb_pre q_urb_pre p_urb_post q_urb_post ///
         p_rur_pre q_rur_pre p_rur_post q_rur_post

* Global
glcurve cons_pre    [aw=weight_indiv], lorenz pvar(p_pre)    glvar(q_pre)    replace
glcurve cons_pc     [aw=weight_indiv], lorenz pvar(p_post)   glvar(q_post)   replace

* Urbain
glcurve cons_pre    [aw=weight_indiv] if area==1, lorenz pvar(p_urb_pre) glvar(q_urb_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==1, lorenz pvar(p_urb_post)glvar(q_urb_post)replace

* Rural
glcurve cons_pre    [aw=weight_indiv] if area==2, lorenz pvar(p_rur_pre) glvar(q_rur_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==2, lorenz pvar(p_rur_post)glvar(q_rur_post)replace

* Superposé – Global
twoway ///
    (line q_pre  p_pre,    sort lpattern(solid))     ///
    (line q_post p_post,   sort lpattern(dash))      ///
    (function y=x,        range(0 1) lpattern(dot)), ///
    title("Lorenz – Global (Scénario 3)") ///
    legend(order(1 "Pré" 2 "Post" 3 "45°")) ///
    xtitle("Pop cumulée") ytitle("Consommation cumulée")
graph export "lorenz_global_s3.png", replace

* Superposé – Urbain
twoway ///
    (line q_urb_pre  p_urb_pre,    sort lpattern(solid))     ///
    (line q_urb_post p_urb_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Lorenz – Urbain (Scénario 3)") ///
    legend(order(1 "Pré" 2 "Post" 3 "45°")) ///
    xtitle("Pop cumulée") ytitle("Consommation cumulée")
graph export "lorenz_urbain_s3.png", replace

* Superposé – Rural
twoway ///
    (line q_rur_pre  p_rur_pre,    sort lpattern(solid))     ///
    (line q_rur_post p_rur_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Lorenz – Rural (Scénario 3)") ///
    legend(order(1 "Pré" 2 "Post" 3 "45°")) ///
    xtitle("Pop cumulée") ytitle("Consommation cumulée")
graph export "lorenz_rural_s3.png", replace

************************************************************
**** Sauvegarde de la base du scénario 3 ****
************************************************************
save "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenario3_bebe_analyse.dta", replace

************************************************************
**** SCÉNARIO 4 – Transfert ciblé : bébé <2 ans ET rural ****
************************************************************

* 0. Charger la base des scénarios
use "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenarios.dta", clear

* 1. Préparer les variables
rename hhweight weight
rename pcexp    cons_pc
rename zref     poverty_line
rename milieu   area
rename hhsize   size
gen weight_indiv = weight * size
gen cons_pre     = cons_pc

************************************************************
**** Analyse PRÉ-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre = (cons_pre < poverty_line)
gen gap    = pauvre * (poverty_line - cons_pre) / poverty_line
gen sq_gap = gap^2

* Global
summ pauvre [aw=weight_indiv]
scalar P0_pre     = r(mean)*100
summ gap [aw=weight_indiv]
scalar P1_pre     = r(mean)*100
summ sq_gap [aw=weight_indiv]
scalar P2_pre     = r(mean)*100
cap which ineqdeco
if _rc != 0 ssc install ineqdeco
ineqdeco cons_pre [aw=weight_indiv]
scalar Gini_pre   = r(gini)*100

* Urbain
summ pauvre [aw=weight_indiv] if area==1
scalar P0_pre_urb = r(mean)*100
summ gap [aw=weight_indiv]    if area==1
scalar P1_pre_urb = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==1
scalar P2_pre_urb = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==1
scalar Gini_pre_urb = r(gini)*100

* Rural
summ pauvre [aw=weight_indiv] if area==2
scalar P0_pre_rur = r(mean)*100
summ gap [aw=weight_indiv]    if area==2
scalar P1_pre_rur = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==2
scalar P2_pre_rur = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==2
scalar Gini_pre_rur = r(gini)*100

************************************************************
**** Transfert ciblé – Bébé <2 ans & Rural
************************************************************
gen transfert = 0
replace transfert = 100000 if bebe==1 & area==2
replace cons_pc   = cons_pre + (transfert/size)

************************************************************
**** Analyse POST-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre2 = (cons_pc < poverty_line)
gen gap2    = pauvre2 * (poverty_line - cons_pc) / poverty_line
gen sq_gap2 = gap2^2

* Global
summ pauvre2 [aw=weight_indiv]
scalar P0_post     = r(mean)*100
summ gap2 [aw=weight_indiv]
scalar P1_post     = r(mean)*100
summ sq_gap2 [aw=weight_indiv]
scalar P2_post     = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv]
scalar Gini_post   = r(gini)*100

* Urbain
summ pauvre2 [aw=weight_indiv] if area==1
scalar P0_post_urb = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==1
scalar P1_post_urb = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==1
scalar P2_post_urb = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==1
scalar Gini_post_urb = r(gini)*100

* Rural
summ pauvre2 [aw=weight_indiv] if area==2
scalar P0_post_rur = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==2
scalar P1_post_rur = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==2
scalar P2_post_rur = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==2
scalar Gini_post_rur = r(gini)*100

************************************************************
**** Coût et part dans le PIB 2023 (en dizaines de milliards) ****
************************************************************
gen cost_hh      = transfert * weight
summ cost_hh
scalar Total_cost = r(sum)
scalar Cost_decab = Total_cost/1e10

************************************************************
**** Efficacité (par dizaine de milliard CFA) ****
************************************************************
scalar Eff_P0_glob    = (P0_pre   - P0_post)   / Cost_decab
scalar Eff_P1_glob    = (P1_pre   - P1_post)   / Cost_decab
scalar Eff_P2_glob    = (P2_pre   - P2_post)   / Cost_decab
scalar Eff_Gini_glob  = (Gini_pre - Gini_post) / Cost_decab

scalar Eff_P0_urb     = (P0_pre_urb   - P0_post_urb)   / Cost_decab
scalar Eff_P1_urb     = (P1_pre_urb   - P1_post_urb)   / Cost_decab
scalar Eff_P2_urb     = (P2_pre_urb   - P2_post_urb)   / Cost_decab
scalar Eff_Gini_urb   = (Gini_pre_urb - Gini_post_urb) / Cost_decab

scalar Eff_P0_rur     = (P0_pre_rur   - P0_post_rur)   / Cost_decab
scalar Eff_P1_rur     = (P1_pre_rur   - P1_post_rur)   / Cost_decab
scalar Eff_P2_rur     = (P2_pre_rur   - P2_post_rur)   / Cost_decab
scalar Eff_Gini_rur   = (Gini_pre_rur - Gini_post_rur) / Cost_decab

************************************************************
**** Tableau récapitulatif
************************************************************
matrix results = ( ///  
    P0_pre,      P1_pre,      P2_pre,      Gini_pre       \  ///
    P0_post,     P1_post,     P2_post,     Gini_post      \  ///
    Eff_P0_glob, Eff_P1_glob, Eff_P2_glob, Eff_Gini_glob  \  ///
    P0_pre_urb,  P1_pre_urb,  P2_pre_urb,  Gini_pre_urb   \  ///
    P0_post_urb, P1_post_urb, P2_post_urb, Gini_post_urb  \  ///
    Eff_P0_urb,  Eff_P1_urb,  Eff_P2_urb,  Eff_Gini_urb   \  ///
    P0_pre_rur,  P1_pre_rur,  P2_pre_rur,  Gini_pre_rur   \  ///
    P0_post_rur, P1_post_rur, P2_post_rur, Gini_post_rur  \  ///
    Eff_P0_rur,  Eff_P1_rur,  Eff_P2_rur,  Eff_Gini_rur      ///
)
matrix rownames results = Avant_Global Après_Global Efficacité_Global ///
                         Avant_Urbain Après_Urbain Efficacité_Urbain ///
                         Avant_Rural Après_Rural Efficacité_Rural
matrix colnames results = P0 P1 P2 Gini
matlist results, format(%9.2f)

************************************************************
**** Courbes de Lorenz – Global, Urbain et Rural ****
************************************************************
cap drop p_pre q_pre p_post q_post ///
         p_urb_pre q_urb_pre p_urb_post q_urb_post ///
         p_rur_pre q_rur_pre p_rur_post q_rur_post

* Global
glcurve cons_pre    [aw=weight_indiv], lorenz pvar(p_pre)    glvar(q_pre)    replace
glcurve cons_pc     [aw=weight_indiv], lorenz pvar(p_post)   glvar(q_post)   replace
twoway ///
    (line q_pre  p_pre,    sort lpattern(solid))     ///
    (line q_post p_post,   sort lpattern(dash))      ///
    (function y=x,        range(0 1) lpattern(dot)), ///
    title("Lorenz – Global (Scénario 4)") ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°")) ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_global_s4.png", replace

* Urbain
glcurve cons_pre    [aw=weight_indiv] if area==1, lorenz pvar(p_urb_pre) glvar(q_urb_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==1, lorenz pvar(p_urb_post)glvar(q_urb_post)replace
twoway ///
    (line q_urb_pre  p_urb_pre,    sort lpattern(solid))     ///
    (line q_urb_post p_urb_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Lorenz – Urbain (Scénario 4)") ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°")) ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_urbain_s4.png", replace

* Rural
glcurve cons_pre    [aw=weight_indiv] if area==2, lorenz pvar(p_rur_pre) glvar(q_rur_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==2, lorenz pvar(p_rur_post)glvar(q_rur_post)replace
twoway ///
    (line q_rur_pre  p_rur_pre,    sort lpattern(solid))     ///
    (line q_rur_post p_rur_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Lorenz – Rural (Scénario 4)") ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°")) ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_rural_s4.png", replace

************************************************************
**** Sauvegarde de la base du scénario 4 ****
************************************************************
save "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenario4_bebe_rural_analyse.dta", replace

************************************************************
**** SCÉNARIO 5 – Transfert ciblé : bébé <2 ans ET rural ****
************************************************************

* 0. Charger la base des scénarios
use "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenarios.dta", clear

* 1. Préparer les variables
rename hhweight weight
rename pcexp    cons_pc
rename zref     poverty_line
rename milieu   area
rename hhsize   size
gen weight_indiv = weight * size
gen cons_pre     = cons_pc

************************************************************
**** Analyse PRÉ-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre = (cons_pre < poverty_line)
gen gap    = pauvre * (poverty_line - cons_pre) / poverty_line
gen sq_gap = gap^2

* Global
summ pauvre [aw=weight_indiv]
scalar P0_pre     = r(mean)*100
summ gap [aw=weight_indiv]
scalar P1_pre     = r(mean)*100
summ sq_gap [aw=weight_indiv]
scalar P2_pre     = r(mean)*100
cap which ineqdeco
if _rc != 0 ssc install ineqdeco
ineqdeco cons_pre [aw=weight_indiv]
scalar Gini_pre   = r(gini)*100

* Urbain
summ pauvre [aw=weight_indiv] if area==1
scalar P0_pre_urb = r(mean)*100
summ gap [aw=weight_indiv]    if area==1
scalar P1_pre_urb = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==1
scalar P2_pre_urb = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==1
scalar Gini_pre_urb = r(gini)*100

* Rural
summ pauvre [aw=weight_indiv] if area==2
scalar P0_pre_rur = r(mean)*100
summ gap [aw=weight_indiv]    if area==2
scalar P1_pre_rur = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==2
scalar P2_pre_rur = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==2
scalar Gini_pre_rur = r(gini)*100

************************************************************
**** Transfert ciblé – Bébé <2 ans & Rural
************************************************************
gen transfert = 0
replace transfert = 100000 if bebe==1 & area==2
replace cons_pc   = cons_pre + (transfert/size)

************************************************************
**** Analyse POST-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre2 = (cons_pc < poverty_line)
gen gap2    = pauvre2 * (poverty_line - cons_pc) / poverty_line
gen sq_gap2 = gap2^2

* Global
summ pauvre2 [aw=weight_indiv]
scalar P0_post     = r(mean)*100
summ gap2 [aw=weight_indiv]
scalar P1_post     = r(mean)*100
summ sq_gap2 [aw=weight_indiv]
scalar P2_post     = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv]
scalar Gini_post   = r(gini)*100

* Urbain
summ pauvre2 [aw=weight_indiv] if area==1
scalar P0_post_urb = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==1
scalar P1_post_urb = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==1
scalar P2_post_urb = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==1
scalar Gini_post_urb = r(gini)*100

* Rural
summ pauvre2 [aw=weight_indiv] if area==2
scalar P0_post_rur = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==2
scalar P1_post_rur = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==2
scalar P2_post_rur = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==2
scalar Gini_post_rur = r(gini)*100

************************************************************
**** Coût et part dans le PIB 2023 (en dizaines de milliards) ****
************************************************************
gen cost_hh      = transfert * weight
summ cost_hh
scalar Total_cost = r(sum)
scalar Cost_decab = Total_cost/1e10

************************************************************
**** Efficacité (par dizaine de milliard CFA) ****
************************************************************
scalar Eff_P0_glob    = (P0_pre   - P0_post)   / Cost_decab
scalar Eff_P1_glob    = (P1_pre   - P1_post)   / Cost_decab
scalar Eff_P2_glob    = (P2_pre   - P2_post)   / Cost_decab
scalar Eff_Gini_glob  = (Gini_pre - Gini_post) / Cost_decab

scalar Eff_P0_urb     = (P0_pre_urb   - P0_post_urb)   / Cost_decab
scalar Eff_P1_urb     = (P1_pre_urb   - P1_post_urb)   / Cost_decab
scalar Eff_P2_urb     = (P2_pre_urb   - P2_post_urb)   / Cost_decab
scalar Eff_Gini_urb   = (Gini_pre_urb - Gini_post_urb) / Cost_decab

scalar Eff_P0_rur     = (P0_pre_rur   - P0_post_rur)   / Cost_decab
scalar Eff_P1_rur     = (P1_pre_rur   - P1_post_rur)   / Cost_decab
scalar Eff_P2_rur     = (P2_pre_rur   - P2_post_rur)   / Cost_decab
scalar Eff_Gini_rur   = (Gini_pre_rur - Gini_post_rur) / Cost_decab

************************************************************
**** Tableau récapitulatif
************************************************************
matrix results = ( ///  
    P0_pre,      P1_pre,      P2_pre,      Gini_pre       \  ///
    P0_post,     P1_post,     P2_post,     Gini_post      \  ///
    Eff_P0_glob, Eff_P1_glob, Eff_P2_glob, Eff_Gini_glob  \  ///
    P0_pre_urb,  P1_pre_urb,  P2_pre_urb,  Gini_pre_urb   \  ///
    P0_post_urb, P1_post_urb, P2_post_urb, Gini_post_urb  \  ///
    Eff_P0_urb,  Eff_P1_urb,  Eff_P2_urb,  Eff_Gini_urb   \  ///
    P0_pre_rur,  P1_pre_rur,  P2_pre_rur,  Gini_pre_rur   \  ///
    P0_post_rur, P1_post_rur, P2_post_rur, Gini_post_rur  \  ///
    Eff_P0_rur,  Eff_P1_rur,  Eff_P2_rur,  Eff_Gini_rur      ///
)
matrix rownames results = Avant_Global Après_Global Efficacité_Global ///
                         Avant_Urbain Après_Urbain Efficacité_Urbain ///
                         Avant_Rural Après_Rural Efficacité_Rural
matrix colnames results = P0 P1 P2 Gini
matlist results, format(%9.2f)

************************************************************
**** Courbes de Lorenz – Global, Urbain et Rural ****
************************************************************
cap drop p_pre q_pre p_post q_post ///
         p_urb_pre q_urb_pre p_urb_post q_urb_post ///
         p_rur_pre q_rur_pre p_rur_post q_rur_post

* Global
glcurve cons_pre    [aw=weight_indiv], lorenz pvar(p_pre)    glvar(q_pre)    replace
glcurve cons_pc     [aw=weight_indiv], lorenz pvar(p_post)   glvar(q_post)   replace
twoway ///
    (line q_pre    p_pre,    sort lpattern(solid))     ///
    (line q_post   p_post,   sort lpattern(dash))      ///
    (function y=x,        range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Global (Scénario 5)")  ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°"))  ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_global_s5.png", replace

* Urbain
glcurve cons_pre    [aw=weight_indiv] if area==1, lorenz pvar(p_urb_pre) glvar(q_urb_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==1, lorenz pvar(p_urb_post)glvar(q_urb_post)replace
twoway ///
    (line q_urb_pre  p_urb_pre,    sort lpattern(solid))     ///
    (line q_urb_post p_urb_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Urbain (Scénario 5)")  ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°"))  ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_urbain_s5.png", replace

* Rural
glcurve cons_pre    [aw=weight_indiv] if area==2, lorenz pvar(p_rur_pre) glvar(q_rur_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==2, lorenz pvar(p_rur_post)glvar(q_rur_post)replace
twoway ///
    (line q_rur_pre  p_rur_pre,    sort lpattern(solid))     ///
    (line q_rur_post p_rur_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Rural (Scénario 5)")  ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°"))  ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_rural_s5.png", replace
************************************************************
**** Sauvegarde de la base du scénario 5 ****
************************************************************
save "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenario5_bebe_rural_analyse.dta", replace

************************************************************
**** SCÉNARIO 6 – Transfert aux ménages avec enfants <18 ans ****
************************************************************

* 0. Charger la base des scénarios
use "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenarios.dta", clear

* 1. Préparer les variables
rename hhweight weight
rename pcexp    cons_pc
rename zref     poverty_line
rename milieu   area
rename hhsize   size
gen weight_indiv = weight * size
gen cons_pre     = cons_pc

************************************************************
**** Analyse PRÉ-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre  = (cons_pre < poverty_line)
gen gap     = pauvre * (poverty_line - cons_pre) / poverty_line
gen sq_gap  = gap^2

* Global
summ pauvre [aw=weight_indiv]
scalar P0_pre     = r(mean)*100
summ gap [aw=weight_indiv]
scalar P1_pre     = r(mean)*100
summ sq_gap [aw=weight_indiv]
scalar P2_pre     = r(mean)*100
cap which ineqdeco
if _rc != 0 ssc install ineqdeco
ineqdeco cons_pre [aw=weight_indiv]
scalar Gini_pre   = r(gini)*100

* Urbain
summ pauvre [aw=weight_indiv] if area==1
scalar P0_pre_urb = r(mean)*100
summ gap [aw=weight_indiv]    if area==1
scalar P1_pre_urb = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==1
scalar P2_pre_urb = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==1
scalar Gini_pre_urb = r(gini)*100

* Rural
summ pauvre [aw=weight_indiv] if area==2
scalar P0_pre_rur = r(mean)*100
summ gap [aw=weight_indiv]    if area==2
scalar P1_pre_rur = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==2
scalar P2_pre_rur = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==2
scalar Gini_pre_rur = r(gini)*100

************************************************************
**** Transfert ciblé – Enfants <18 ans
************************************************************
gen transfert = 0
replace transfert = 100000 if under18==1
replace cons_pc   = cons_pre + (transfert/size)

************************************************************
**** Analyse POST-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre2 = (cons_pc < poverty_line)
gen gap2    = pauvre2 * (poverty_line - cons_pc) / poverty_line
gen sq_gap2 = gap2^2

* Global
summ pauvre2 [aw=weight_indiv]
scalar P0_post     = r(mean)*100
summ gap2 [aw=weight_indiv]
scalar P1_post     = r(mean)*100
summ sq_gap2 [aw=weight_indiv]
scalar P2_post     = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv]
scalar Gini_post   = r(gini)*100

* Urbain
summ pauvre2 [aw=weight_indiv] if area==1
scalar P0_post_urb = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==1
scalar P1_post_urb = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==1
scalar P2_post_urb = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==1
scalar Gini_post_urb = r(gini)*100

* Rural
summ pauvre2 [aw=weight_indiv] if area==2
scalar P0_post_rur = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==2
scalar P1_post_rur = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==2
scalar P2_post_rur = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==2
scalar Gini_post_rur = r(gini)*100

************************************************************
**** Coût et part dans le PIB 2023 (en dizaines de milliards) ****
************************************************************
gen cost_hh      = transfert * weight
summ cost_hh
scalar Total_cost = r(sum)
scalar Cost_decab = Total_cost/1e10

************************************************************
**** Efficacité (par dizaine de milliard CFA) ****
************************************************************
scalar Eff_P0_glob    = (P0_pre   - P0_post)   / Cost_decab
scalar Eff_P1_glob    = (P1_pre   - P1_post)   / Cost_decab
scalar Eff_P2_glob    = (P2_pre   - P2_post)   / Cost_decab
scalar Eff_Gini_glob  = (Gini_pre - Gini_post) / Cost_decab

scalar Eff_P0_urb     = (P0_pre_urb   - P0_post_urb)   / Cost_decab
scalar Eff_P1_urb     = (P1_pre_urb   - P1_post_urb)   / Cost_decab
scalar Eff_P2_urb     = (P2_pre_urb   - P2_post_urb)   / Cost_decab
scalar Eff_Gini_urb   = (Gini_pre_urb - Gini_post_urb) / Cost_decab

scalar Eff_P0_rur     = (P0_pre_rur   - P0_post_rur)   / Cost_decab
scalar Eff_P1_rur     = (P1_pre_rur   - P1_post_rur)   / Cost_decab
scalar Eff_P2_rur     = (P2_pre_rur   - P2_post_rur)   / Cost_decab
scalar Eff_Gini_rur   = (Gini_pre_rur - Gini_post_rur) / Cost_decab

************************************************************
**** Tableau récapitulatif
************************************************************
matrix results = ( ///  
    P0_pre,      P1_pre,      P2_pre,      Gini_pre       \  ///
    P0_post,     P1_post,     P2_post,     Gini_post      \  ///
    Eff_P0_glob, Eff_P1_glob, Eff_P2_glob, Eff_Gini_glob  \  ///
    P0_pre_urb,  P1_pre_urb,  P2_pre_urb,  Gini_pre_urb   \  ///
    P0_post_urb, P1_post_urb, P2_post_urb, Gini_post_urb  \  ///
    Eff_P0_urb,  Eff_P1_urb,  Eff_P2_urb,  Eff_Gini_urb   \  ///
    P0_pre_rur,  P1_pre_rur,  P2_pre_rur,  Gini_pre_rur   \  ///
    P0_post_rur, P1_post_rur, P2_post_rur, Gini_post_rur  \  ///
    Eff_P0_rur,  Eff_P1_rur,  Eff_P2_rur,  Eff_Gini_rur      ///
)
matrix rownames results = Avant_Global Après_Global Efficacité_Global ///
                         Avant_Urbain Après_Urbain Efficacité_Urbain ///
                         Avant_Rural Après_Rural Efficacité_Rural
matrix colnames results = P0 P1 P2 Gini
matlist results, format(%9.2f)

************************************************************
**** Courbes de Lorenz – Global, Urbain et Rural ****
************************************************************
cap drop p_pre q_pre p_post q_post ///
         p_urb_pre q_urb_pre p_urb_post q_urb_post ///
         p_rur_pre q_rur_pre p_rur_post q_rur_post

* Global
glcurve cons_pre [aw=weight_indiv], lorenz pvar(p_pre) glvar(q_pre) replace
glcurve cons_pc  [aw=weight_indiv], lorenz pvar(p_post) glvar(q_post) replace
twoway ///
    (line q_pre  p_pre,    sort lpattern(solid))     ///
    (line q_post p_post,   sort lpattern(dash))      ///
    (function y=x,        range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Global (Scénario 6)") ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°")) ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_global_s6.png", replace

* Urbain
glcurve cons_pre    [aw=weight_indiv] if area==1, lorenz pvar(p_urb_pre) glvar(q_urb_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==1, lorenz pvar(p_urb_post)glvar(q_urb_post)replace
twoway ///
    (line q_urb_pre  p_urb_pre,    sort lpattern(solid))     ///
    (line q_urb_post p_urb_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Urbain (Scénario 6)") ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°")) ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_urbain_s6.png", replace

* Rural
glcurve cons_pre    [aw=weight_indiv] if area==2, lorenz pvar(p_rur_pre) glvar(q_rur_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==2, lorenz pvar(p_rur_post)glvar(q_rur_post)replace
twoway ///
    (line q_rur_pre  p_rur_pre,    sort lpattern(solid))     ///
    (line q_rur_post p_rur_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Rural (Scénario 6)") ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°")) ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_rural_s6.png", replace

************************************************************
**** Sauvegarde de la base du scénario 6 ****
************************************************************
save "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenario6_under18_analyse.dta", replace

************************************************************
**** SCÉNARIO 7 – Transfert aux ménages avec personnes âgées ****
************************************************************

* 0. Charger la base des scénarios
use "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenarios.dta", clear

* 1. Préparer les variables
rename hhweight weight
rename pcexp    cons_pc
rename zref     poverty_line
rename milieu   area
rename hhsize   size
gen weight_indiv = weight * size
gen cons_pre     = cons_pc

************************************************************
**** Analyse PRÉ-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre = (cons_pre < poverty_line)
gen gap    = pauvre * (poverty_line - cons_pre) / poverty_line
gen sq_gap = gap^2

* Global
summ pauvre [aw=weight_indiv]
scalar P0_pre     = r(mean)*100
summ gap [aw=weight_indiv]
scalar P1_pre     = r(mean)*100
summ sq_gap [aw=weight_indiv]
scalar P2_pre     = r(mean)*100
cap which ineqdeco
if _rc != 0 ssc install ineqdeco
ineqdeco cons_pre [aw=weight_indiv]
scalar Gini_pre   = r(gini)*100

* Urbain
summ pauvre [aw=weight_indiv] if area==1
scalar P0_pre_urb = r(mean)*100
summ gap [aw=weight_indiv]    if area==1
scalar P1_pre_urb = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==1
scalar P2_pre_urb = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==1
scalar Gini_pre_urb = r(gini)*100

* Rural
summ pauvre [aw=weight_indiv] if area==2
scalar P0_pre_rur = r(mean)*100
summ gap [aw=weight_indiv]    if area==2
scalar P1_pre_rur = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==2
scalar P2_pre_rur = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==2
scalar Gini_pre_rur = r(gini)*100

************************************************************
**** Transfert ciblé – Personnes âgées
************************************************************
gen transfert = 0
replace transfert = 100000 if elder==1
replace cons_pc   = cons_pre + (transfert/size)

************************************************************
**** Analyse POST-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre2 = (cons_pc < poverty_line)
gen gap2    = pauvre2 * (poverty_line - cons_pc) / poverty_line
gen sq_gap2 = gap2^2

* Global
summ pauvre2 [aw=weight_indiv]
scalar P0_post     = r(mean)*100
summ gap2 [aw=weight_indiv]
scalar P1_post     = r(mean)*100
summ sq_gap2 [aw=weight_indiv]
scalar P2_post     = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv]
scalar Gini_post   = r(gini)*100

* Urbain
summ pauvre2 [aw=weight_indiv] if area==1
scalar P0_post_urb = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==1
scalar P1_post_urb = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==1
scalar P2_post_urb = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==1
scalar Gini_post_urb = r(gini)*100

* Rural
summ pauvre2 [aw=weight_indiv] if area==2
scalar P0_post_rur = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==2
scalar P1_post_rur = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==2
scalar P2_post_rur = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==2
scalar Gini_post_rur = r(gini)*100

************************************************************
**** Coût et part dans le PIB 2023 (en dizaines de milliards) ****
************************************************************
gen cost_hh      = transfert * weight
summ cost_hh
scalar Total_cost = r(sum)
scalar Cost_decab = Total_cost/1e10

************************************************************
**** Efficacité (par dizaine de milliard CFA) ****
************************************************************
scalar Eff_P0_glob    = (P0_pre   - P0_post)   / Cost_decab
scalar Eff_P1_glob    = (P1_pre   - P1_post)   / Cost_decab
scalar Eff_P2_glob    = (P2_pre   - P2_post)   / Cost_decab
scalar Eff_Gini_glob  = (Gini_pre - Gini_post) / Cost_decab

scalar Eff_P0_urb     = (P0_pre_urb   - P0_post_urb)   / Cost_decab
scalar Eff_P1_urb     = (P1_pre_urb   - P1_post_urb)   / Cost_decab
scalar Eff_P2_urb     = (P2_pre_urb   - P2_post_urb)   / Cost_decab
scalar Eff_Gini_urb   = (Gini_pre_urb - Gini_post_urb) / Cost_decab

scalar Eff_P0_rur     = (P0_pre_rur   - P0_post_rur)   / Cost_decab
scalar Eff_P1_rur     = (P1_pre_rur   - P1_post_rur)   / Cost_decab
scalar Eff_P2_rur     = (P2_pre_rur   - P2_post_rur)   / Cost_decab
scalar Eff_Gini_rur   = (Gini_pre_rur - Gini_post_rur) / Cost_decab

************************************************************
**** Tableau récapitulatif
************************************************************
matrix results = ( ///  
    P0_pre,      P1_pre,      P2_pre,      Gini_pre       \  ///
    P0_post,     P1_post,     P2_post,     Gini_post      \  ///
    Eff_P0_glob, Eff_P1_glob, Eff_P2_glob, Eff_Gini_glob  \  ///
    P0_pre_urb,  P1_pre_urb,  P2_pre_urb,  Gini_pre_urb   \  ///
    P0_post_urb, P1_post_urb, P2_post_urb, Gini_post_urb  \  ///
    Eff_P0_urb,  Eff_P1_urb,  Eff_P2_urb,  Eff_Gini_urb   \  ///
    P0_pre_rur,  P1_pre_rur,  P2_pre_rur,  Gini_pre_rur   \  ///
    P0_post_rur, P1_post_rur, P2_post_rur, Gini_post_rur  \  ///
    Eff_P0_rur,  Eff_P1_rur,  Eff_P2_rur,  Eff_Gini_rur      ///
)
matrix rownames results = Avant_Global Après_Global Efficacité_Global ///
                         Avant_Urbain Après_Urbain Efficacité_Urbain ///
                         Avant_Rural Après_Rural Efficacité_Rural
matrix colnames results = P0 P1 P2 Gini
matlist results, format(%9.2f)

************************************************************
**** Courbes de Lorenz – Global, Urbain et Rural ****
************************************************************
cap drop p_pre q_pre p_post q_post ///
         p_urb_pre q_urb_pre p_urb_post q_urb_post ///
         p_rur_pre q_rur_pre p_rur_post q_rur_post

* Global
glcurve cons_pre [aw=weight_indiv], lorenz pvar(p_pre) glvar(q_pre) replace
glcurve cons_pc  [aw=weight_indiv], lorenz pvar(p_post) glvar(q_post) replace
twoway ///
    (line q_pre  p_pre,    sort lpattern(solid))     ///
    (line q_post p_post,   sort lpattern(dash))      ///
    (function y=x,        range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Global (Scénario 7)") ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°")) ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_global_s7.png", replace

* Urbain
glcurve cons_pre    [aw=weight_indiv] if area==1, lorenz pvar(p_urb_pre) glvar(q_urb_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==1, lorenz pvar(p_urb_post)glvar(q_urb_post)replace
twoway ///
    (line q_urb_pre  p_urb_pre,    sort lpattern(solid))     ///
    (line q_urb_post p_urb_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Urbain (Scénario 7)") ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°")) ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_urbain_s7.png", replace

* Rural
glcurve cons_pre    [aw=weight_indiv] if area==2, lorenz pvar(p_rur_pre) glvar(q_rur_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==2, lorenz pvar(p_rur_post)glvar(q_rur_post)replace
twoway ///
    (line q_rur_pre  p_rur_pre,    sort lpattern(solid))     ///
    (line q_rur_post p_rur_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Rural (Scénario 7)") ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°")) ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_rural_s7.png", replace

************************************************************
**** Sauvegarde de la base du scénario 7 ****
************************************************************
save "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenario7_elderly_analyse.dta", replace

************************************************************
**** SCÉNARIO 8 – Transfert aux ménages avec handicapés ****
************************************************************

* 0. Charger la base des scénarios
use "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenarios.dta", clear

* 1. Préparer les variables
rename hhweight weight
rename pcexp    cons_pc
rename zref     poverty_line
rename milieu   area
rename hhsize   size
gen weight_indiv = weight * size
gen cons_pre     = cons_pc

************************************************************
**** Analyse PRÉ-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre = (cons_pre < poverty_line)
gen gap    = pauvre * (poverty_line - cons_pre) / poverty_line
gen sq_gap = gap^2

* Global
summ pauvre [aw=weight_indiv]
scalar P0_pre     = r(mean)*100
summ gap [aw=weight_indiv]
scalar P1_pre     = r(mean)*100
summ sq_gap [aw=weight_indiv]
scalar P2_pre     = r(mean)*100
cap which ineqdeco
if _rc != 0 ssc install ineqdeco
ineqdeco cons_pre [aw=weight_indiv]
scalar Gini_pre   = r(gini)*100

* Urbain
summ pauvre [aw=weight_indiv] if area==1
scalar P0_pre_urb = r(mean)*100
summ gap [aw=weight_indiv]    if area==1
scalar P1_pre_urb = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==1
scalar P2_pre_urb = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==1
scalar Gini_pre_urb = r(gini)*100

* Rural
summ pauvre [aw=weight_indiv] if area==2
scalar P0_pre_rur = r(mean)*100
summ gap [aw=weight_indiv]    if area==2
scalar P1_pre_rur = r(mean)*100
summ sq_gap [aw=weight_indiv] if area==2
scalar P2_pre_rur = r(mean)*100
ineqdeco cons_pre [aw=weight_indiv] if area==2
scalar Gini_pre_rur = r(gini)*100

************************************************************
**** Transfert ciblé – Handicapés
************************************************************
gen transfert = 0
replace transfert = 100000 if handicap==1
replace cons_pc   = cons_pre + (transfert/size)

************************************************************
**** Analyse POST-transfert – Global, Urbain, Rural ****
************************************************************
gen pauvre2 = (cons_pc < poverty_line)
gen gap2    = pauvre2 * (poverty_line - cons_pc) / poverty_line
gen sq_gap2 = gap2^2

* Global
summ pauvre2 [aw=weight_indiv]
scalar P0_post     = r(mean)*100
summ gap2 [aw=weight_indiv]
scalar P1_post     = r(mean)*100
summ sq_gap2 [aw=weight_indiv]
scalar P2_post     = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv]
scalar Gini_post   = r(gini)*100

* Urbain
summ pauvre2 [aw=weight_indiv] if area==1
scalar P0_post_urb = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==1
scalar P1_post_urb = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==1
scalar P2_post_urb = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==1
scalar Gini_post_urb = r(gini)*100

* Rural
summ pauvre2 [aw=weight_indiv] if area==2
scalar P0_post_rur = r(mean)*100
summ gap2 [aw=weight_indiv]    if area==2
scalar P1_post_rur = r(mean)*100
summ sq_gap2 [aw=weight_indiv] if area==2
scalar P2_post_rur = r(mean)*100
ineqdeco cons_pc [aw=weight_indiv] if area==2
scalar Gini_post_rur = r(gini)*100

************************************************************
**** Coût et part dans le PIB 2023 (en dizaines de milliards) ****
************************************************************
gen cost_hh      = transfert * weight
summ cost_hh
scalar Total_cost = r(sum)
scalar Cost_decab = Total_cost/1e10

************************************************************
**** Efficacité (par dizaine de milliard CFA) ****
************************************************************
scalar Eff_P0_glob   = (P0_pre   - P0_post)   / Cost_decab
scalar Eff_P1_glob   = (P1_pre   - P1_post)   / Cost_decab
scalar Eff_P2_glob   = (P2_pre   - P2_post)   / Cost_decab
scalar Eff_Gini_glob = (Gini_pre - Gini_post) / Cost_decab

scalar Eff_P0_urb    = (P0_pre_urb   - P0_post_urb)   / Cost_decab
scalar Eff_P1_urb    = (P1_pre_urb   - P1_post_urb)   / Cost_decab
scalar Eff_P2_urb    = (P2_pre_urb   - P2_post_urb)   / Cost_decab
scalar Eff_Gini_urb  = (Gini_pre_urb - Gini_post_urb) / Cost_decab

scalar Eff_P0_rur    = (P0_pre_rur   - P0_post_rur)   / Cost_decab
scalar Eff_P1_rur    = (P1_pre_rur   - P1_post_rur)   / Cost_decab
scalar Eff_P2_rur    = (P2_pre_rur   - P2_post_rur)   / Cost_decab
scalar Eff_Gini_rur  = (Gini_pre_rur - Gini_post_rur) / Cost_decab

************************************************************
**** Tableau récapitulatif
************************************************************
matrix results = ( ///  
    P0_pre,      P1_pre,      P2_pre,      Gini_pre       \  ///
    P0_post,     P1_post,     P2_post,     Gini_post      \  ///
    Eff_P0_glob, Eff_P1_glob, Eff_P2_glob, Eff_Gini_glob  \  ///
    P0_pre_urb,  P1_pre_urb,  P2_pre_urb,  Gini_pre_urb   \  ///
    P0_post_urb, P1_post_urb, P2_post_urb, Gini_post_urb  \  ///
    Eff_P0_urb,  Eff_P1_urb,  Eff_P2_urb,  Eff_Gini_urb   \  ///
    P0_pre_rur,  P1_pre_rur,  P2_pre_rur,  Gini_pre_rur   \  ///
    P0_post_rur, P1_post_rur, P2_post_rur, Gini_post_rur  \  ///
    Eff_P0_rur,  Eff_P1_rur,  Eff_P2_rur,  Eff_Gini_rur      ///
)
matrix rownames results = Avant_Global Après_Global Efficacité_Global ///
                         Avant_Urbain Après_Urbain Efficacité_Urbain ///
                         Avant_Rural Après_Rural Efficacité_Rural
matrix colnames results = P0 P1 P2 Gini
matlist results, format(%9.2f)

************************************************************
**** Courbes de Lorenz – Global, Urbain et Rural ****
************************************************************
cap drop p_pre q_pre p_post q_post ///
         p_urb_pre q_urb_pre p_urb_post q_urb_post ///
         p_rur_pre q_rur_pre p_rur_post q_rur_post

* Global
glcurve cons_pre    [aw=weight_indiv], lorenz pvar(p_pre)    glvar(q_pre)    replace
glcurve cons_pc     [aw=weight_indiv], lorenz pvar(p_post)   glvar(q_post)   replace
twoway ///
    (line q_pre    p_pre,    sort lpattern(solid))     ///
    (line q_post   p_post,   sort lpattern(dash))      ///
    (function y=x,        range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Global (Scénario 8)")  ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°"))  ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_global_s8.png", replace

* Urbain
glcurve cons_pre    [aw=weight_indiv] if area==1, lorenz pvar(p_urb_pre) glvar(q_urb_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==1, lorenz pvar(p_urb_post)glvar(q_urb_post)replace
twoway ///
    (line q_urb_pre  p_urb_pre,    sort lpattern(solid))     ///
    (line q_urb_post p_urb_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Urbain (Scénario 8)")  ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°"))  ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_urbain_s8.png", replace

* Rural
glcurve cons_pre    [aw=weight_indiv] if area==2, lorenz pvar(p_rur_pre) glvar(q_rur_pre) replace
glcurve cons_pc     [aw=weight_indiv] if area==2, lorenz pvar(p_rur_post)glvar(q_rur_post)replace
twoway ///
    (line q_rur_pre  p_rur_pre,    sort lpattern(solid))     ///
    (line q_rur_post p_rur_post,   sort lpattern(dash))      ///
    (function y=x,                    range(0 1) lpattern(dot)), ///
    title("Courbe de Lorenz – Rural (Scénario 8)")  ///
    legend(order(1 "Pré-transfert" 2 "Post-transfert" 3 "45°"))  ///
    xtitle("Population cumulée") ytitle("Consommation cumulée")
graph export "lorenz_rural_s8.png", replace

************************************************************
**** Sauvegarde de la base du scénario 8 ****
************************************************************
save "C:\Intel\AS2\S2\Développement et conditions de vie des ménages\EHCVM\scenario8_handicap_analyse.dta", replace

