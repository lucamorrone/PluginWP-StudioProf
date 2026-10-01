# Studio Professionale - Plugin WordPress

**Versione corrente: 2.2.7 Responsive**

Plugin completo per WordPress dedicato alla **gestione dell'anagrafica pazienti, cartella clinica e anamnesi, visite e sedute, documenti clinici, contabilità sanitaria, fatturazione, Proforma, pagamenti ed esportazione per il commercialista**.

La versione 2.2.7 introduce un'interfaccia responsive per smartphone e tablet, mantenendo la logica applicativa e le funzionalità validate nella versione stabile 2.2.6.

## Caratteristiche principali

- **Sicurezza WordPress:** sanitizzazione degli input, nonce per form ed endpoint AJAX e controllo dei permessi tramite capability.
- **Database personalizzato:** tabelle dedicate create con `$wpdb` e `dbDelta`, separate dai normali post di WordPress.
- **Ruoli applicativi:** Amministratore, Professionista e Segreteria Studio con permessi differenziati.
- **Audit:** registrazione di accessi e operazioni rilevanti, con indirizzo IP conservato esclusivamente come hash.
- **Responsive:** interfaccia ottimizzata per desktop, tablet e smartphone.
- **PDF integrati:** generazione di fatture, Proforma, consenso privacy e report del commercialista senza dipendenze Composer.
- **Esportazioni:** CSV UTF-8, report PDF multipagina e pacchetti ZIP per il commercialista.

## Requisiti

- Installazione WordPress funzionante.
- Hosting Linux con PHP e MySQL/MariaDB compatibili con WordPress.
- Permessi di scrittura nella directory `wp-content/uploads`.
- Estensione PHP `ZipArchive` necessaria per creare il pacchetto ZIP del commercialista.
- Sistema email WordPress correttamente configurato. È consigliato un plugin SMTP, ad esempio FluentSMTP.

## Struttura del plugin

```text
studio-professionale-wp/
├── studio-professionale.php
├── README.md
├── CHANGELOG.md
├── assets/
│   ├── css/
│   │   └── studio-admin.css
│   └── js/
│       └── studio-admin.js
├── includes/
│   ├── class-studio-activator.php
│   ├── class-studio-roles.php
│   ├── class-studio-db.php
│   ├── class-studio-admin.php
│   ├── class-studio-patients.php
│   ├── class-studio-invoices.php
│   ├── class-studio-pdf.php
│   ├── class-studio-pdf-engine.php
│   ├── class-studio-accountant.php
│   ├── class-studio-documents.php
│   └── class-studio-security.php
└── templates/
    ├── dashboard.php
    ├── studio-settings.php
    ├── patients-list.php
    ├── patient-form.php
    ├── patient-detail.php
    ├── patient-import.php
    ├── invoices-list.php
    ├── invoice-form.php
    ├── invoice-detail.php
    ├── accountant-export.php
    ├── report-accountant-html.php
    └── security-log.php
```

## Installazione e aggiornamento

### Nuova installazione

1. Copiare la cartella `studio-professionale-wp` in:

   ```text
   /wp-content/plugins/studio-professionale-wp/
   ```

2. Accedere al pannello di amministrazione WordPress.
3. Aprire **Plugin > Plugin installati**.
4. Attivare **Studio Professionale - Gestione Pazienti & Fatturazione**.
5. Aprire **Studio > Anagrafica Studio** e completare la configurazione.

### Aggiornamento da una versione precedente

1. Creare un backup del database e della cartella del plugin.
2. Installare o sostituire il plugin con il nuovo pacchetto.
3. Aprire **Studio > Anagrafica Studio**.
4. Selezionare **Ripara / Verifica Tabelle DB**.
5. Verificare ruoli, impostazioni fiscali, email e valore della marca da bollo.

## Tabelle database

Il plugin utilizza le seguenti tabelle, con il prefisso configurato nell'installazione WordPress:

```text
wp_studio_pazienti
wp_studio_visite
wp_studio_fatture
wp_studio_fatture_righe
wp_studio_impostazioni
wp_studio_documenti
wp_studio_log
wp_studio_audit
```

Nella tabella delle fatture il campo `applica_marca_bollo` conserva la scelta manuale dell'utente, mentre `marca_bollo` contiene l'importo economico applicato.

## Ruoli e permessi

### Amministratore

- Accesso completo a tutte le funzionalità.
- Configurazione dello studio e dei parametri fiscali.
- Gestione utenti e ruoli.
- Eliminazione di pazienti consentita solo se non esistono fatture emesse.
- Eliminazione delle sole bozze non emesse.
- Consultazione e pulizia controllata dei log.

### Professionista

- Gestione pazienti, cartella clinica, anamnesi e visite.
- Gestione documenti clinici.
- Gestione fatture, pagamenti, PDF ed email.
- Consultazione del registro di sicurezza.
- Nessun accesso alla configurazione generale dello studio.

### Segreteria Studio

- Gestione delle anagrafiche pazienti.
- Gestione di bozze, fatture, pagamenti e invii email.
- Nessun accesso ad anamnesi, visite, documenti clinici, impostazioni dello studio e registro di sicurezza.

## Guida alle funzionalità

### 1. Dashboard

La Dashboard presenta una panoramica operativa con:

- totale pazienti;
- suddivisione tra maschi e femmine;
- fatture gestite nell'anno selezionato;
- suddivisione tra fatture emesse e in bozza;
- incassato dell'anno;
- importi ancora da incassare;
- ultime fatture;
- ultimi pazienti inseriti;
- filtro per anno.

### 2. Anagrafica Studio e tariffario

Aprire **Studio > Anagrafica Studio** per configurare:

- denominazione dello studio;
- nominativo e titolo del professionista;
- Codice Fiscale e Partita IVA;
- indirizzo, telefono, email, PEC e sito web;
- IBAN, banca e BIC/SWIFT;
- cassa previdenziale e relativa aliquota;
- importo della marca da bollo;
- soglia di esenzione del bollo;
- ritenuta d'acconto;
- note legali;
- testo del consenso privacy;
- email del commercialista;
- prestazioni e tariffe predefinite.

### 3. Pazienti

L'area pazienti consente di:

- creare, cercare, modificare ed eliminare anagrafiche secondo i vincoli previsti;
- validare il Codice Fiscale, incluso il carattere di controllo;
- impedire Codici Fiscali duplicati;
- ricavare automaticamente data di nascita e sesso dal Codice Fiscale;
- mostrare l'età nella scheda del paziente;
- registrare telefono, email, residenza e note;
- gestire anamnesi clinica con salvataggio AJAX;
- registrare visite e sedute con data, durata, tipologia e note;
- consultare il riepilogo contabile del paziente;
- visualizzare fatture emesse, pagate e ancora da saldare;
- consultare lo storico delle comunicazioni e degli invii.

### 4. Consenso privacy

Dalla scheda paziente è possibile:

- generare e stampare il consenso privacy precompilato;
- scaricare il PDF;
- inviare il consenso al paziente via email;
- registrare la generazione o l'invio nel log;
- conservare data e utente dell'operazione.

Il PDF del consenso utilizza un layout in bianco e nero adatto alla stampa e alla firma.

### 5. Documenti clinici

Gli utenti autorizzati possono:

- caricare esclusivamente documenti PDF validi;
- associare una descrizione al documento;
- scaricare i documenti mediante endpoint autenticato;
- eliminare documenti secondo i permessi assegnati;
- registrare caricamenti ed eliminazioni nel registro di audit.

I documenti sono archiviati in una sottocartella dedicata per ciascun paziente.

### 6. Importazione pazienti da CSV

Aprire **Pazienti > Importa CSV** e caricare un file CSV UTF-8.

Il sistema riconosce intestazioni come:

```text
Cognome
Nome
CodiceFiscale oppure CF
Telefono oppure Cellulare
Email
Indirizzo
CAP
Citta
Provincia
DataNascita
LuogoNascita
ProvinciaNascita
StatoNascita
Sesso
Note
```

Sono supportati i separatori:

- virgola;
- punto e virgola;
- tabulazione.

L'importazione esegue:

- controllo del Codice Fiscale;
- controllo dei CF già presenti nel database;
- controllo dei duplicati nello stesso CSV;
- validazione di telefono ed email;
- normalizzazione dei telefoni in notazione scientifica Excel;
- normalizzazione delle date nei formati `AAAA-MM-GG`, `GG/MM/AAAA`, `GG-MM-AAAA` e `AAAA/MM/GG`;
- log degli errori con numero di riga e descrizione della causa.

### 7. Esportazione anagrafiche pazienti

Il pulsante **Export CSV** esporta tutte le anagrafiche in formato CSV UTF-8 con separatore punto e virgola, compatibile con Excel italiano.

### 8. Fatturazione

Il flusso di fatturazione consente di:

- selezionare il paziente;
- scegliere una prestazione predefinita oppure inserire una descrizione libera;
- gestire più righe;
- configurare quantità, prezzo unitario e sconto;
- calcolare imponibile, cassa previdenziale, IVA, ritenuta e marca da bollo;
- registrare metodo e data di pagamento;
- inserire note nel documento;
- generare PDF;
- inviare il documento via email.

### 9. Proforma e fattura definitiva

#### Proforma

Quando il documento è in bozza:

- non riceve una numerazione fiscale definitiva;
- viene mostrato come **Proforma** nell'interfaccia;
- il PDF riporta il titolo **PROFORMA**;
- il piè di pagina contiene la seguente dicitura:

> Il presente documento non costituisce fattura valida ai fini del DpR 633/1972 e successive modifiche. La fattura definitiva verrà emessa all’atto del pagamento del corrispettivo (articolo 6, comma3, DpR633/72).

#### Emissione

L'azione **Emetti Fattura**:

- assegna il numero progressivo annuale;
- registra anno, codice e data di emissione;
- blocca la modifica dei dati fiscali;
- genera il PDF definitivo.

### 10. Marca da bollo

La marca da bollo è gestita in modo coerente durante:

- creazione e modifica della bozza;
- visualizzazione della Proforma;
- calcolo del totale;
- emissione;
- PDF;
- invio email;
- report del commercialista;
- export CSV e ZIP.

Il flag `applica_marca_bollo` è la scelta persistente dell'utente. L'importo viene recuperato dai parametri dello studio e sincronizzato con il totale del documento.

### 11. Stato del pagamento

Le fatture possono essere:

- **Da pagare**, quando non è presente una data di pagamento;
- **Saldate**, quando vengono registrati data e metodo di pagamento.

L'aggiornamento del pagamento rigenera il PDF con lo stato e la quietanza aggiornati.

### 12. Eliminazione delle bozze

- È possibile eliminare esclusivamente documenti non emessi nello stato bozza.
- L'eliminazione avviene tramite richiesta POST protetta da nonce.
- Un documento emesso non può essere eliminato con la funzione dedicata alle bozze.

### 13. Sezione Commercialista

Aprire **Studio > Commercialista** e selezionare:

- anno;
- intero anno;
- trimestre;
- singolo mese;
- intervallo personalizzato.

Sono disponibili:

#### CSV fiscale

Contiene dati della fattura e del paziente, inclusi:

- numero e data documento;
- imponibile;
- cassa previdenziale;
- IVA;
- bollo;
- ritenuta;
- totale;
- stato, data e metodo di pagamento;
- dati anagrafici del paziente.

#### Pacchetto ZIP

Contiene:

- riepilogo CSV;
- tutte le fatture PDF del periodo selezionato.

#### Report PDF

- include tutte le fatture del periodo;
- genera automaticamente più pagine quando necessario;
- ripete intestazioni e colonne nelle pagine successive;
- include la colonna IVA prima del totale;
- mostra imponibile, cassa, IVA, bollo e totale;
- viene utilizzato sia per il download sia come allegato email al commercialista.

#### Log invii

Gli invii al commercialista vengono registrati con destinatario, periodo, oggetto, operatore e numero di documenti elaborati.

### 14. Sicurezza e audit

Il plugin registra eventi come:

- accesso alle pagine del plugin;
- login;
- creazione, modifica ed eliminazione di anagrafiche;
- modifiche dell'anamnesi;
- inserimento o eliminazione delle visite;
- caricamento o eliminazione dei documenti;
- generazione e invio del consenso privacy;
- eliminazione delle bozze;
- invio dei report al commercialista;
- pulizia dei log.

Il registro può essere filtrato per:

- testo;
- utente;
- azione;
- ultimi giorni;
- intervallo di date.

La pulizia dei log è riservata agli amministratori e viene essa stessa registrata nell'audit.

### 15. Utilizzo da smartphone e tablet

La versione 2.2.7 Responsive introduce:

- dashboard adattiva;
- moduli a larghezza piena sugli smartphone;
- pulsanti più ampi per uso touch;
- tabelle scorrevoli orizzontalmente senza perdita di colonne;
- schede paziente scorrevoli;
- riepiloghi fattura adattati alla larghezza dello schermo;
- interfaccia tablet a due colonne quando lo spazio disponibile lo consente.

Su schermi piccoli viene mostrato il messaggio:

```text
Scorri orizzontalmente per visualizzare tutte le colonne.
```

## Percorsi e archiviazione

I PDF generati e i documenti clinici vengono archiviati sotto:

```text
/wp-content/uploads/studio-professionale-docs/
```

I documenti clinici sono suddivisi in cartelle per paziente.

## Email

Il plugin utilizza `wp_mail()` per:

- fatture e Proforma;
- consenso privacy;
- report del commercialista.

Per consegne affidabili è consigliato configurare un servizio SMTP e verificare mittente, autenticazione e recapito degli allegati.

## Backup e manutenzione

Prima di ogni aggiornamento:

1. eseguire il backup del database;
2. copiare la cartella del plugin;
3. copiare la directory dei documenti;
4. conservare il pacchetto ZIP dell'ultima versione stabile.

Dopo ogni aggiornamento:

1. eseguire **Ripara / Verifica Tabelle DB**;
2. verificare i ruoli;
3. testare un paziente;
4. testare una Proforma con bollo;
5. emettere una fattura di prova;
6. verificare PDF, email e report commercialista.

## Changelog sintetico

### Versione 2.2.7 Responsive

- Interfaccia responsive per smartphone e tablet.
- Tabelle scorrevoli senza perdita di dati o azioni.
- Campi e pulsanti ottimizzati per il touch.
- Schede paziente navigabili su schermi piccoli.
- Nessuna modifica alla logica applicativa della stable 2.2.6.

### Versione 2.2.6 Stable

- Gestione delle bozze come Proforma.
- Dicitura fiscale nel PDF della Proforma.
- Correzione definitiva della marca da bollo in salvataggio, visualizzazione, emissione, PDF, email ed export.
- Sincronizzazione tra flag bollo, importo e totale documento.
- Collaudo end-to-end completato.

### Versione 2.2.5

- Campo database dedicato `applica_marca_bollo`.
- Persistenza del checkbox della marca da bollo in modifica.

### Versione 2.2.4

- Dashboard con dettaglio maschi, femmine, fatture emesse e bozze.
- Import CSV con controllo CF esistenti e duplicati.
- Correzione delle date di nascita e dei telefoni in notazione scientifica.
- Export CSV completo delle anagrafiche.
- Età nella scheda paziente.
- Log dettagliato degli errori di importazione.
- Correzione dell'eliminazione delle bozze.
- Report commercialista multipagina con tutte le fatture e colonna IVA.

### Versione 2.2.3

- Report periodico in PDF orizzontale.
- Consenso privacy in bianco e nero.
- Testo email del consenso aggiornato.
- Eliminazione dei log per intervallo di date con audit.
- Età nell'elenco pazienti.

### Versione 2.2.2

- Finestra per stampa, download o invio email del consenso privacy.
- Registrazione degli invii del consenso.
- Percentuali fiscali nel PDF della fattura.
- Marca da bollo controllabile manualmente.
- Conferma e messaggio di esito nell'invio al commercialista.
- Correzione delle estensioni PDF nel pacchetto ZIP.

### Versione 2.2.1

- Validazione completa e unicità del Codice Fiscale.
- Telefono ed email obbligatori.
- Vincoli di eliminazione per pazienti e fatture.
- Ricerca avanzata nel registro di sicurezza.
- Log degli invii al commercialista.

### Versione 2.2.0

- Dashboard filtrabile per anno.
- Paginazione pazienti e fatture.
- Documenti clinici PDF.
- Consenso privacy e log comunicazioni.
- Export CSV e ZIP per il commercialista.
- Ruoli Professionista e Segreteria.
- Registro accessi e audit.

### Versione 2.1.0

- Motore PDF vettoriale integrato.
- Ripristino del layout grafico di fatture e consenso privacy.

### Versione 2.0.0

- Prima implementazione della generazione PDF.

## Note finali

- Il plugin tratta dati personali, sanitari e fiscali. L'installazione, la configurazione, i backup, la protezione dell'hosting e la corretta attribuzione dei ruoli devono essere gestiti con attenzione.
- Prima dell'utilizzo in produzione verificare sempre parametri fiscali, note legali, contenuto del consenso privacy e configurazione email con il professionista e i consulenti competenti.
- Conservare il file `CHANGELOG.md` per il dettaglio completo delle modifiche di ogni release.
