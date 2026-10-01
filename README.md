# Studio Professionale - Plugin WordPress

Plugin completo per WordPress dedicato alla **gestione dell'anagrafica pazienti, cartella clinica/anamnesi, visite/sedute, contabilità sanitaria con calcolo automatico imposte, fatturazione ed esportazione per il commercialista**.

Progettato rispettando gli standard di sicurezza di WordPress:
- **Sicurezza:** Sanitizzazione di tutti gli input (`sanitize_text_field`, `sanitize_email`, `wp_kses_post`), Nonce di verifica per form ed endpoint AJAX, controllo stretto dei permessi con `current_user_can`.
- **Database Personalizzato:** Tabelle dedicate create con `$wpdb` e `dbDelta` per massime prestazioni e isolamento dei dati sanitari rispetto ai normali post di WordPress.
- **Ruolo Personalizzato "Segretaria":** Permette al personale dello studio di gestire le anagrafiche, le bozze e l'invio delle fatture, riservando la configurazione fiscale e le cancellazioni agli amministratori.

---

## 📁 Struttura del Plugin

```text
studio-professionale-wp/
├── studio-professionale.php        # Entry point principale del plugin
├── README.md                       # Questa guida di installazione ed uso
├── assets/
│   ├── css/
│   │   └── studio-admin.css        # Stili dell'interfaccia di amministrazione
│   └── js/
│       └── studio-admin.js         # Logica interattiva (calcolo CF, AJAX, calcoli fattura)
├── includes/
│   ├── class-studio-activator.php  # Creazione tabelle DB e cartelle protette uploads
│   ├── class-studio-roles.php      # Registrazione ruoli e permessi (Segretaria / Admin)
│   ├── class-studio-db.php         # Gestione parametri e query helper su $wpdb
│   ├── class-studio-admin.php      # Controller menu, dashboard e impostazioni studio
│   ├── class-studio-patients.php   # CRUD Pazienti, decodifica CF, storico visite e import CSV
│   ├── class-studio-invoices.php   # Flusso fatturazione (Bozza/Emissione), pagamenti e quietanze
│   ├── class-studio-pdf.php        # Generatore documenti A4 (Fattura e Consenso Privacy) ed email
│   └── class-studio-accountant.php # Esportazione commercialista (CSV, ZIP con fatture, Report PDF)
└── templates/
    ├── dashboard.php               # Dashboard di riepilogo
    ├── studio-settings.php         # Pagina anagrafica studio e tariffario
    ├── patients-list.php           # Elenco pazienti con ricerca
    ├── patient-form.php            # Form inserimento/modifica paziente
    ├── patient-detail.php          # Cartella clinica con Tab (Anamnesi, Visite, Contabilità)
    ├── patient-import.php          # Maschera di importazione CSV
    ├── invoices-list.php           # Elenco fatture con filtri
    ├── invoice-form.php            # Compilazione fattura con calcoli automatici
    ├── invoice-detail.php          # Dettaglio fattura e quietanza
    ├── accountant-export.php       # Pannello esportazione commercialista
    └── report-accountant-html.php  # Layout di stampa del riepilogo fiscale
```

---

## ⚙️ Installazione e Attivazione

1. Copia o sposta l'intera cartella `studio-professionale-wp` all'interno della cartella dei plugin di WordPress:
   ```text
   /wp-content/plugins/studio-professionale-wp
   ```
2. Accedi al pannello di amministrazione di WordPress (`wp-admin`).
3. Vai su **Plugin -> Plugin installati**.
4. Cerca **Studio Professionale - Gestione Pazienti & Fatturazione** e clicca su **Attiva**.
5. All'attivazione, il plugin creerà automaticamente nel database le tabelle dedicate:
   - `wp_studio_pazienti`
   - `wp_studio_visite`
   - `wp_studio_fatture`
   - `wp_studio_fatture_righe`
   - `wp_studio_impostazioni`
   e registrerà il ruolo utente **Segretaria Studio** (`studio_segretaria`).

---

## 🚀 Guida all'Uso delle Funzionalità

### 1. Anagrafica Studio e Tariffario
- Vai nel menu **Studio -> Anagrafica Studio**.
- Inserisci la denominazione dello studio, il nome del professionista, Codice Fiscale, Partita IVA, IBAN bancario, aliquota cassa di previdenza (es. ENPAP 2%), soglia marca da bollo (77.47 €) e note legali (es. regime forfettario).
- Configura il tariffario con le prestazioni standard (es. *Colloquio clinico*, *Seduta terapia di coppia*, *Valutazione psicodiagnostica*).

### 2. Anagrafica Pazienti & Calcolo Codice Fiscale
- Clicca su **Pazienti -> Nuovo Paziente**.
- Digitando il Codice Fiscale (16 caratteri), il modulo decodifica in tempo reale la **Data di Nascita** e il **Sesso** compilandoli istantaneamente nel form.
- Dalla scheda dettaglio paziente avrai a disposizione:
  - **Anamnesi Clinica:** un editor con salvataggio rapido asincrono per la storia clinica protetta da segreto professionale.
  - **Storico Sedute/Visite:** modulo per registrare data, durata, tipo seduta e resoconto colloqui.
  - **Contabilità Paziente:** riepilogo immediato di fatturato totale, pagato e saldo residuo.
  - **Modulo Consenso Privacy GDPR:** genera con un clic il consenso informato sanitario precompilato pronto da stampare e far firmare.

### 3. Importazione Massiva CSV
- Se possiedi già un elenco pazienti (da foglio Excel o altro gestionale), vai su **Pazienti -> Importa CSV**.
- Carica il file: il plugin mappa automaticamente i campi anche se il file ha colonne in ordine differente (`Cognome`, `Nome`, `CodiceFiscale`, `Telefono`, `Email`, `Indirizzo`, ecc.) e valida i dati.

### 4. Flusso Fatturazione e Quietanza Pagamenti
- Clicca su **Fatture -> Nuova Fattura**.
- Seleziona il paziente e la prestazione dal menu: il totale imponibile, la cassa di previdenza, l'eventuale IVA e la marca da bollo vengono calcolati in tempo reale.
- **Bozza:** Salva il documento per revisioni future senza impegnare la numerazione fiscale.
- **Emetti Fattura:** Assegna in modo incrementale il numero progressivo ufficiale per l'anno di competenza (es. `1/2026`) e blocca la modificabilità dei dati fiscali.
- **Stato Pagamento:** Se non è indicata la data di saldo, la fattura riporterà l'etichetta **"DA PAGARE"** e le coordinate IBAN. Non appena il paziente salda il conto (in contanti, bonifico o carta), registrando la data la fattura passerà a **"SALDATO"** con quietanza.
- **Generazione e Invio:** Generazione della copia PDF/stampa archiviata in `/wp-content/uploads/studio-professionale-docs/` e invio diretto via email con allegato.

### 5. Sezione Commercialista
- Vai su **Studio -> Commercialista**.
- Seleziona l'anno e il periodo (Intero anno, singolo trimestre o mese specifico, oppure intervallo date personalizzato).
- Scarica con un clic:
  - **File CSV / Excel:** Dati completi di tutte le fatture emesse con imponibile, cassa, bolli, data incasso e dati anagrafici completi dei pazienti (per compilazione dichiarazione e modello 730/Redditi/Tessera Sanitaria).
  - **Pacchetto ZIP:** Archivio contenente sia il CSV riepilogativo sia tutte le copie dei documenti di spesa sanitaria emessi nel periodo.
  - **Report Fiscale di Riepilogo:** Riepilogo stampabile da allegare al fascicolo contabile.


### Versione 2.2.5
- Correzione definitiva della persistenza della marca da bollo mediante campo database dedicato.


### Versione 2.2.6
- Sincronizzazione definitiva del bollo in tutte le pagine, emissione, PDF ed export.


### Versione 2.2.7
- Interfaccia responsive per smartphone e tablet, senza modifiche alla logica applicativa.
