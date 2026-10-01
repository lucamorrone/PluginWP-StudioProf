# Changelog

Tutte le modifiche rilevanti del plugin **Studio Professionale - Gestione Pazienti e Fatturazione** sono documentate in questo file.

Il formato segue i principi di *Keep a Changelog*. Le versioni utilizzano una numerazione progressiva compatibile con il versionamento semantico.

## [2.2.7-responsive] - Responsive mobile e tablet

### Aggiunto

- Layout responsive dedicato a smartphone e tablet.
- Tabelle scorrevoli orizzontalmente su schermi piccoli, senza nascondere colonne, dati o azioni.
- Messaggio mobile che invita a scorrere orizzontalmente le tabelle.
- Ottimizzazione touch per pulsanti, checkbox, radio button, campi e selettori.
- Navigazione orizzontale delle schede della cartella paziente su smartphone.

### Modificato

- Dashboard disposta su una colonna negli smartphone e su due colonne nei tablet.
- Moduli adattati automaticamente alla larghezza dello schermo.
- Pulsanti operativi distribuiti su più righe o in griglia negli schermi più piccoli.
- Riepiloghi e totali delle fatture adattati alla larghezza disponibile.
- Pannelli, intestazioni, margini e spaziature ottimizzati per l'utilizzo in mobilità.
- Campi impostati con dimensioni adatte all'utilizzo touch e alla prevenzione dello zoom automatico sui dispositivi mobili.

### Compatibilità

- Nessuna modifica alla logica applicativa o alla struttura del database.
- Mantenute tutte le funzionalità della versione stabile 2.2.6.
- Mantenute la gestione Proforma e la correzione definitiva della marca da bollo.

---

## [2.2.6] - Stable

### Aggiunto

- Visualizzazione delle bozze come **Proforma**.
- Titolo `PROFORMA` nel PDF dei documenti ancora in bozza.
- Dicitura fiscale nel piè di pagina del PDF della Proforma:

  > Il presente documento non costituisce fattura valida ai fini del DpR 633/1972 e successive modifiche. La fattura definitiva verrà emessa all’atto del pagamento del corrispettivo (articolo 6, comma3, DpR633/72).

- Funzione centralizzata per determinare l'importo effettivo della marca da bollo.
- Controllo esplicito degli errori durante il salvataggio delle fatture.

### Corretto

- Correzione definitiva della marca da bollo durante l'intero ciclo del documento:
  - creazione della bozza;
  - salvataggio;
  - riapertura in modifica;
  - visualizzazione del dettaglio;
  - calcolo del totale;
  - emissione definitiva;
  - generazione PDF;
  - invio via email;
  - esportazione CSV;
  - pacchetto ZIP;
  - report per il commercialista.
- Sincronizzazione tra `applica_marca_bollo`, `marca_bollo` e `totale_documento`.
- Ricalcolo del bollo e del totale prima della visualizzazione, dell'emissione e della stampa.
- Corretto il selettore JavaScript del checkbox della marca da bollo.
- Gestito il recupero dell'importo del bollo dalla configurazione dello studio, dal valore salvato o dal valore standard di 2,00 euro.
- Corretta la visualizzazione del bollo nel dettaglio della fattura e nel PDF.
- Corretto il trasferimento del bollo nei report e negli export del commercialista.

### Verificato

- Collaudo completo del flusso bozza, modifica, Proforma, emissione, PDF definitivo, email ed export commercialista.
- Versione dichiarata stabile dopo il completamento dei test funzionali.

---

## [2.2.5] - Persistenza marca da bollo

### Aggiunto

- Nuovo campo database `applica_marca_bollo` nella tabella delle fatture.
- Memorizzazione persistente della scelta manuale relativa alla marca da bollo.
- Compatibilità con le fatture precedenti attraverso il valore già presente nel campo `marca_bollo`.

### Corretto

- Il checkbox della marca da bollo rimane selezionato dopo il salvataggio e la riapertura della fattura in modifica.

### Nota

- La versione ha corretto la persistenza del flag, ma non ancora la sincronizzazione completa dell'importo economico in dettaglio, emissione, PDF e report. La correzione completa è stata introdotta nella versione 2.2.6.

---

## [2.2.4] - Dashboard, importazione CSV e report commercialista

### Dashboard

#### Aggiunto

- Dettaglio del totale pazienti suddiviso in:
  - maschi;
  - femmine.
- Dettaglio delle fatture gestite suddiviso in:
  - emesse;
  - in bozza.

### Pazienti

#### Aggiunto

- Pulsante **Export CSV** per esportare tutti i dati anagrafici dei pazienti.
- Età del paziente nella testata della scheda, accanto al nome.
- Log dettagliato degli errori di importazione CSV con indicazione della riga e della causa.
- Controllo dei Codici Fiscali già presenti nel database.
- Controllo dei Codici Fiscali duplicati nello stesso file CSV.
- Normalizzazione automatica delle date nei formati:
  - `AAAA-MM-GG`;
  - `GG/MM/AAAA`;
  - `GG-MM-AAAA`;
  - `AAAA/MM/GG`.
- Conversione delle date nel formato database `AAAA-MM-GG`.
- Gestione dei numeri telefonici trasformati da Excel in notazione scientifica.

#### Corretto

- Corretto l'importazione CSV che consentiva duplicati del Codice Fiscale.
- Corretta l'importazione errata della data di nascita.
- Corretto il calcolo anomalo dell'età in presenza di date non valide.
- Migliorata la validazione di telefono ed email durante l'importazione.

### Fatture

#### Corretto

- Corretto il pulsante **Elimina Bozza**, che mostrava il messaggio `Il link che hai seguito è scaduto`.
- Eliminazione della bozza convertita in operazione POST protetta da nonce.
- Mantenuto il vincolo che consente di eliminare esclusivamente fatture non emesse nello stato bozza.
- Prima correzione della memorizzazione della scelta manuale della marca da bollo.

### Commercialista

#### Aggiunto

- Report PDF multipagina.
- Ripetizione dell'intestazione e delle colonne nelle pagine successive.
- Colonna IVA inserita prima del totale.

#### Corretto

- Eliminato il limite che mostrava nel report solo le prime 16 fatture.
- Inclusione di tutte le fatture del periodo selezionato.
- Utilizzo dello stesso report multipagina per download e invio email al commercialista.

---

## [2.2.3] - Report, privacy, audit ed età pazienti

### Commercialista

#### Aggiunto

- Report completo con:
  - intestazione del professionista;
  - periodo selezionato;
  - numero di documenti emessi;
  - totale imponibile;
  - cassa previdenziale;
  - bolli applicati;
  - totale generale;
  - incassato effettivo;
  - tabella fiscale dettagliata.
- Conversione del report in PDF orizzontale.
- Utilizzo del report PDF per download e invio email.

### Privacy

#### Modificato

- Consenso privacy convertito in documento completamente bianco e nero.
- Aggiornato il testo dell'email del consenso privacy.
- Richiesta di restituzione del modulo firmato via email oppure tramite consegna cartacea allo studio.

### Sicurezza

#### Aggiunto

- Eliminazione dei log per intervallo di date.
- Registrazione dell'evento `pulizia_log` con:
  - data iniziale;
  - data finale;
  - numero dei record eliminati;
  - utente che ha eseguito l'operazione.
- Conferma prima dell'eliminazione definitiva dei log.

### Pazienti

#### Aggiunto

- Calcolo automatico dell'età nell'elenco pazienti.

---

## [2.2.2] - Consenso privacy, PDF fattura ed export

### Consenso privacy

#### Aggiunto

- Scelta tramite finestra tra:
  - stampa o download del consenso;
  - invio via email.
- Registrazione dell'invio del consenso nel log del paziente e nel registro di sicurezza.

### Fatture

#### Aggiunto

- Percentuali di cassa previdenziale, IVA e ritenuta nel PDF della fattura.
- Visualizzazione dello sconto nel PDF solo quando maggiore di zero.

#### Modificato

- Marca da bollo resa controllabile manualmente tramite checkbox, anche sopra la soglia configurata.

### Commercialista

#### Aggiunto

- Conferma prima dell'invio del report al commercialista.
- Messaggio di conferma dopo l'invio.

#### Corretto

- Corrette le estensioni delle fatture presenti nel pacchetto ZIP da HTML a PDF.
- Rimossa la sezione informativa `Cosa Contengono gli Export`.

---

## [2.2.1] - Validazioni, cancellazioni e ricerca audit

### Dashboard

#### Modificato

- Esteso il filtro anno anche al totale delle fatture gestite.

### Pazienti

#### Aggiunto

- Validazione completa del Codice Fiscale, incluso il carattere di controllo.
- Controllo di unicità del Codice Fiscale.
- Telefono ed email obbligatori e validati.
- Eliminazione del paziente consentita solo quando non sono presenti fatture emesse.

#### Modificato

- Pulsante di salvataggio del paziente spostato nella parte superiore della pagina.

### Fatture

#### Corretto

- Eliminazione delle fatture limitata alle sole bozze non emesse.

### Commercialista

#### Modificato

- Uniformato l'allegato email al report periodico.
- Migliorati oggetto e testo dell'email destinata al commercialista.

#### Aggiunto

- Log degli invii al commercialista.

### Sicurezza

#### Aggiunto

- Ricerca avanzata nel registro di sicurezza per:
  - testo;
  - utente;
  - azione;
  - ultimi giorni;
  - intervallo di date.

---

## [2.2.0] - Documenti, ruoli, audit ed esportazione commercialista

### Dashboard

#### Aggiunto

- Filtro per anno.

### Pazienti

#### Aggiunto

- Paginazione dell'elenco pazienti.
- Gestione di documenti clinici PDF.
- Memorizzazione dello stato di generazione del consenso privacy.
- Storico delle comunicazioni e degli invii nella scheda paziente.

### Fatture

#### Aggiunto

- Paginazione dell'elenco fatture.
- Oggetto email standard nel formato `Fattura N. 12/2026 Mario Rossi`.
- Importo e stato del pagamento nel testo dell'email della fattura.

#### Modificato

- Layout PDF della fattura reso più sobrio, con sfondo bianco.

### Commercialista

#### Aggiunto

- Configurazione dell'indirizzo email del commercialista.
- Report periodico per anno, trimestre, mese o intervallo personalizzato.
- Invio del report via email.
- Esportazione CSV dei dati fiscali e anagrafici.
- Pacchetto ZIP con CSV e fatture PDF.

### Ruoli e sicurezza

#### Aggiunto

- Ruolo **Professionista**.
- Ruolo **Segreteria Studio**.
- Professionista con accesso alle aree operative e cliniche, ma senza accesso alle impostazioni generali dello studio.
- Segreteria senza accesso ad anamnesi, visite, documenti clinici e registro di sicurezza.
- Registro degli accessi.
- Audit delle modifiche cliniche e amministrative.
- Memorizzazione dell'indirizzo IP esclusivamente come hash.

---

## [2.1.0] - Motore PDF vettoriale

### Aggiunto

- Motore PDF vettoriale integrato senza dipendenze Composer.
- Supporto a:
  - riquadri;
  - tabelle;
  - totali;
  - spazi firma;
  - layout grafico strutturato.

### Corretto

- Ripristinato il layout grafico dei PDF di fattura e consenso privacy.
- Superato il precedente generatore PDF testuale minimale, che non interpretava correttamente HTML e CSS.

---

## [2.0.0] - Generazione documenti PDF

### Aggiunto

- Prima implementazione della generazione PDF per fatture e consenso privacy.

### Limitazioni note

- Generatore PDF testuale minimale.
- Mancata interpretazione completa di HTML e CSS.
- Layout grafico successivamente sostituito dal motore vettoriale della versione 2.1.0.

---

## Convenzioni

- **Aggiunto**: nuove funzionalità.
- **Modificato**: cambiamenti a funzionalità esistenti.
- **Corretto**: risoluzione di bug o comportamenti anomali.
- **Sicurezza**: modifiche che riguardano permessi, audit, protezione dei dati o accessi.
- **Compatibilità**: informazioni sulla continuità con versioni precedenti.
- **Limitazioni note**: problemi conosciuti nella specifica versione e risolti successivamente.
