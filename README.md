# enquete_pedagogique
l'application de gestion des tablettes de l'enquête pédagogique. L'onglet
**Affectation** permet de saisir le nom du bénéficiaire, son identifiant et la
date d'affectation.
L'onglet **Enregistrement** permet d'ajouter une nouvelle tablette en
précisant si un chargeur et une powerbank sont présents.
Vous pouvez également choisir son **statut actuel** parmi plusieurs
options (en stock, affectée, en réparation ou perdue).
Après chaque enregistrement, affectation ou déclaration,
les champs du formulaire sont désormais réinitialisés automatiquement.
Un onglet **Bases** permet également d'ouvrir les fichiers
`tablettes.xlsx`, `affectations.xlsx` et `incidents.xlsx` pour
consulter facilement les données.

## Installation

Installez les dépendances requises :

```bash
pip install -r requirements.txt
```

L'application requiert en particulier la bibliothèque `openpyxl` pour gérer les fichiers Excel. Si vous voyez une erreur `ModuleNotFoundError: No module named 'openpyxl'`, assurez-vous que cette étape d'installation a bien été effectuée.

## Lancer l'application

```bash
python app_tablettes.py
```

## Exécuter l'analyse Stata

Le script `code_prj_1.do` utilise des jeux de données hébergés en ligne sur
[GitHub](https://github.com/Bassongo/poverty-analysis). Les résultats sont
enregistrés dans le répertoire `data` du projet. Pour exécuter l'analyse :

```bash
stata -b do code_prj_1.do
```

Assurez-vous que Stata peut accéder à Internet pour télécharger les fichiers
source lors de l'exécution.
