import os
from datetime import datetime

import pandas as pd
import tkinter as tk
from tkinter import ttk, messagebox, filedialog

# S'assure que le module openpyxl est disponible pour la gestion des fichiers Excel
try:
    import openpyxl  # noqa: F401
except ModuleNotFoundError:
    raise SystemExit(
        "Le module 'openpyxl' est requis. Installez-le avec 'pip install openpyxl'."
    )

# Fichiers Excel utilises par l'application
TABLETTES_FILE = 'tablettes.xlsx'
AFFECT_FILE = 'affectations.xlsx'
INCIDENT_FILE = 'incidents.xlsx'

# Valeurs possibles pour le statut d'une tablette
STATUS_OPTIONS = ['En stock', 'Affectée', 'En réparation', 'Perdue']


def init_files():
    """Cree les fichiers Excel avec les entetes s'ils n'existent pas."""
    if not os.path.exists(TABLETTES_FILE):
        df = pd.DataFrame(
            columns=[
                'N° Tablette',
                'Statut actuel',
                'Chargeur',
                'Powerbank',
                'Observations',
            ]
        )
        df.to_excel(TABLETTES_FILE, index=False, engine='openpyxl')
    if not os.path.exists(AFFECT_FILE):
        df = pd.DataFrame(
            columns=[
                "Date d'affectation",
                'N° Tablette',
                'Nom bénéficiaire',
                'Identifiant bénéficiaire',
            ]
        )
        df.to_excel(AFFECT_FILE, index=False, engine='openpyxl')
    if not os.path.exists(INCIDENT_FILE):
        df = pd.DataFrame(columns=['Date', 'N° Tablette', 'Nature incident', 'Déclarant', 'Lieu'])
        df.to_excel(INCIDENT_FILE, index=False, engine='openpyxl')


def load_tablettes():
    df = pd.read_excel(TABLETTES_FILE, engine='openpyxl')
    if 'Chargeur' not in df.columns:
        df['Chargeur'] = ''
    if 'Powerbank' not in df.columns:
        df['Powerbank'] = ''
    return df


def save_tablettes(df):
    df.to_excel(TABLETTES_FILE, index=False, engine='openpyxl')


def load_affectations():
    df = pd.read_excel(AFFECT_FILE, engine='openpyxl')
    if 'Identifiant bénéficiaire' not in df.columns:
        df['Identifiant bénéficiaire'] = ''
    return df


def save_affectations(df):
    df.to_excel(AFFECT_FILE, index=False, engine='openpyxl')


def load_incidents():
    return pd.read_excel(INCIDENT_FILE, engine='openpyxl')


def save_incidents(df):
    df.to_excel(INCIDENT_FILE, index=False, engine='openpyxl')


def importer_tablettes():
    """Importe un fichier tablettes existant."""
    path = filedialog.askopenfilename(title='Sélectionnez le fichier tablettes',
                                      filetypes=[('Fichiers Excel', '*.xlsx')])
    if path:
        df = pd.read_excel(path, engine='openpyxl')

        # Vérifie la présence des colonnes requises
        required = ['N° Tablette', 'Chargeur', 'Powerbank']
        if not all(col in df.columns for col in required):
            messagebox.showerror(
                'Format invalide',
                "Le fichier doit contenir les colonnes 'N° Tablette', 'Chargeur' et 'Powerbank'.",
            )
            return

        if 'Statut actuel' not in df.columns:
            df['Statut actuel'] = 'En stock'
        if 'Observations' not in df.columns:
            df['Observations'] = 'RAS'

        # Remplit les observations manquantes
        df['Observations'] = df['Observations'].fillna('RAS').replace('', 'RAS')

        df = df[
            ['N° Tablette', 'Statut actuel', 'Chargeur', 'Powerbank', 'Observations']
        ]

        df.to_excel(TABLETTES_FILE, index=False, engine='openpyxl')
        messagebox.showinfo('Import', 'Fichier tablettes importé avec succès.')
        update_dashboard()


def ajouter_tablette():
    num = entry_num_new.get().strip()
    if not num:
        messagebox.showwarning('Champs manquant', 'Veuillez entrer un numéro de tablette.')
        return
    df = load_tablettes()
    if num in df['N° Tablette'].astype(str).values:
        messagebox.showerror('Erreur', 'La tablette existe déjà.')
        return

    df.loc[len(df)] = {
        'N° Tablette': num,
        'Statut actuel': status_var.get(),
        'Chargeur': 'Oui' if charger_var.get() else 'Non',
        'Powerbank': 'Oui' if powerbank_var.get() else 'Non',
        'Observations': 'RAS',
    }
    save_tablettes(df)
    messagebox.showinfo('Succès', 'Tablette enregistrée.')
    update_dashboard()


def assigner_tablette():
    num = entry_num_aff.get().strip()
    nom = entry_nom.get().strip()
    ident = entry_ident.get().strip()
    date = entry_date_aff.get().strip()
    if not num or not nom or not ident or not date:
        messagebox.showwarning('Champs manquants', 'Veuillez remplir tous les champs.')
        return
    df = load_tablettes()
    if num not in df['N° Tablette'].astype(str).values:
        messagebox.showerror('Erreur', "La tablette n'existe pas")
        return
    status = df.loc[df['N° Tablette'].astype(str) == num, 'Statut actuel'].iloc[0]
    if status != 'En stock':
        messagebox.showerror('Indisponible', 'La tablette n\'est pas en stock.')
        return
    df.loc[df['N° Tablette'].astype(str) == num, 'Statut actuel'] = 'Affectée'
    save_tablettes(df)

    df_aff = load_affectations()
    df_aff.loc[len(df_aff)] = {
        "Date d'affectation": date,
        'N° Tablette': num,
        'Nom bénéficiaire': nom,
        'Identifiant bénéficiaire': ident,
    }
    save_affectations(df_aff)
    messagebox.showinfo('Succès', 'Tablette affectée.')
    update_dashboard()


def retour_tablette():
    num = entry_num_retour.get().strip()
    if not num:
        messagebox.showwarning('Champs manquant', 'Veuillez entrer un numéro de tablette.')
        return
    df = load_tablettes()
    if num not in df['N° Tablette'].astype(str).values:
        messagebox.showerror('Erreur', "La tablette n'existe pas")
        return
    df.loc[df['N° Tablette'].astype(str) == num, 'Statut actuel'] = 'En stock'
    save_tablettes(df)
    messagebox.showinfo('Succès', 'Tablette retournée en stock.')
    update_dashboard()


def declarer_incident():
    num = entry_num_inc.get().strip()
    nature = entry_nature.get().strip()
    declarant = entry_declarant.get().strip()
    lieu = entry_lieu.get().strip()
    date = entry_date_inc.get().strip()
    if not all([num, nature, declarant, lieu, date]):
        messagebox.showwarning('Champs manquants', 'Veuillez remplir tous les champs.')
        return
    df_inc = load_incidents()
    df_inc.loc[len(df_inc)] = {
        'Date': date,
        'N° Tablette': num,
        'Nature incident': nature,
        'Déclarant': declarant,
        'Lieu': lieu,
    }
    save_incidents(df_inc)
    messagebox.showinfo('Succès', 'Incident déclaré.')
    update_dashboard()


def update_dashboard():
    df = load_tablettes()
    stock = (df['Statut actuel'] == 'En stock').sum()
    affect = (df['Statut actuel'] == 'Affectée').sum()
    df_inc = load_incidents()
    incidents = len(df_inc)
    label_stock.config(text=f'En stock : {stock}')
    label_affect.config(text=f'Affectées : {affect}')
    label_incident.config(text=f'Incidents : {incidents}')


# Initialisation des fichiers
init_files()

# Interface graphique
root = tk.Tk()
root.title('Gestion des tablettes')
root.geometry('500x450')

# Amélioration de l'apparence avec le thème ttk
style = ttk.Style(root)
style.theme_use('clam')
style.configure('.', font=('Helvetica', 10))

notebook = ttk.Notebook(root)
notebook.pack(expand=1, fill='both')

# --- Onglet Affectation ---
aff_frame = ttk.Frame(notebook)
notebook.add(aff_frame, text='Affectation')

label_num_aff = ttk.Label(aff_frame, text='N° Tablette :')
label_num_aff.grid(row=0, column=0, sticky='e')
entry_num_aff = ttk.Entry(aff_frame)
entry_num_aff.grid(row=0, column=1, pady=2)

label_nom = ttk.Label(aff_frame, text='Nom bénéficiaire :')
label_nom.grid(row=1, column=0, sticky='e')
entry_nom = ttk.Entry(aff_frame)
entry_nom.grid(row=1, column=1, pady=2)

label_ident = ttk.Label(aff_frame, text='ID bénéficiaire :')
label_ident.grid(row=2, column=0, sticky='e')
entry_ident = ttk.Entry(aff_frame)
entry_ident.grid(row=2, column=1, pady=2)

label_date_aff = ttk.Label(aff_frame, text='Date :')
label_date_aff.grid(row=3, column=0, sticky='e')
entry_date_aff = ttk.Entry(aff_frame)
entry_date_aff.insert(0, datetime.today().strftime('%Y-%m-%d'))
entry_date_aff.grid(row=3, column=1, pady=2)

btn_affecter = ttk.Button(aff_frame, text='Affecter', command=assigner_tablette)
btn_affecter.grid(row=4, column=0, columnspan=2, pady=5)

# --- Onglet Retour ---
retour_frame = ttk.Frame(notebook)
notebook.add(retour_frame, text='Retour')

label_num_retour = ttk.Label(retour_frame, text='N° Tablette :')
label_num_retour.grid(row=0, column=0, sticky='e')
entry_num_retour = ttk.Entry(retour_frame)
entry_num_retour.grid(row=0, column=1, pady=2)

btn_retour = ttk.Button(retour_frame, text='Enregistrer le retour', command=retour_tablette)
btn_retour.grid(row=1, column=0, columnspan=2, pady=5)

# --- Onglet Enregistrement ---
enreg_frame = ttk.Frame(notebook)
notebook.add(enreg_frame, text='Enregistrement')

label_num_new = ttk.Label(enreg_frame, text='N° Tablette :')
label_num_new.grid(row=0, column=0, sticky='e')
entry_num_new = ttk.Entry(enreg_frame)
entry_num_new.grid(row=0, column=1, pady=2)

charger_var = tk.BooleanVar()
check_charger = ttk.Checkbutton(enreg_frame, text='Chargeur présent', variable=charger_var)
check_charger.grid(row=1, column=0, columnspan=2, sticky='w', pady=2)

powerbank_var = tk.BooleanVar()
check_powerbank = ttk.Checkbutton(enreg_frame, text='Powerbank présente', variable=powerbank_var)
check_powerbank.grid(row=2, column=0, columnspan=2, sticky='w', pady=2)

# Sélection du statut initial de la tablette
status_var = tk.StringVar(value=STATUS_OPTIONS[0])
label_status = ttk.Label(enreg_frame, text='Statut actuel :')
label_status.grid(row=3, column=0, sticky='e')
combo_status = ttk.Combobox(
    enreg_frame,
    textvariable=status_var,
    values=STATUS_OPTIONS,
    state='readonly',
)
combo_status.grid(row=3, column=1, pady=2)
combo_status.current(0)

btn_add = ttk.Button(enreg_frame, text='Enregistrer', command=ajouter_tablette)
btn_add.grid(row=4, column=0, columnspan=2, pady=5)

btn_import = ttk.Button(
    enreg_frame,
    text='Importer depuis Excel',
    command=importer_tablettes,
)
btn_import.grid(row=5, column=0, columnspan=2, pady=5)

# --- Onglet Incident ---
incident_frame = ttk.Frame(notebook)
notebook.add(incident_frame, text='Incident')

label_num_inc = ttk.Label(incident_frame, text='N° Tablette :')
label_num_inc.grid(row=0, column=0, sticky='e')
entry_num_inc = ttk.Entry(incident_frame)
entry_num_inc.grid(row=0, column=1, pady=2)

label_nature = ttk.Label(incident_frame, text='Nature :')
label_nature.grid(row=1, column=0, sticky='e')
entry_nature = ttk.Entry(incident_frame)
entry_nature.grid(row=1, column=1, pady=2)

label_declarant = ttk.Label(incident_frame, text='Déclarant :')
label_declarant.grid(row=2, column=0, sticky='e')
entry_declarant = ttk.Entry(incident_frame)
entry_declarant.grid(row=2, column=1, pady=2)

label_lieu = ttk.Label(incident_frame, text='Lieu :')
label_lieu.grid(row=3, column=0, sticky='e')
entry_lieu = ttk.Entry(incident_frame)
entry_lieu.grid(row=3, column=1, pady=2)

label_date_inc = ttk.Label(incident_frame, text='Date :')
label_date_inc.grid(row=4, column=0, sticky='e')
entry_date_inc = ttk.Entry(incident_frame)
entry_date_inc.insert(0, datetime.today().strftime('%Y-%m-%d'))
entry_date_inc.grid(row=4, column=1, pady=2)

btn_incident = ttk.Button(incident_frame, text='Déclarer', command=declarer_incident)
btn_incident.grid(row=5, column=0, columnspan=2, pady=5)

# --- Onglet Tableau de bord ---
db_frame = ttk.Frame(notebook)
notebook.add(db_frame, text='Tableau de bord')

label_stock = ttk.Label(db_frame, text='En stock : 0')
label_stock.pack(pady=2)
label_affect = ttk.Label(db_frame, text='Affectées : 0')
label_affect.pack(pady=2)
label_incident = ttk.Label(db_frame, text='Incidents : 0')
label_incident.pack(pady=2)

update_dashboard()
root.mainloop()
