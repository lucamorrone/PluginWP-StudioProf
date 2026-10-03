# Installazione PWA 2.4.0 su Aruba

1. Eseguire un backup completo di file e database.
2. Verificare che il dominio utilizzi HTTPS.
3. Da WordPress aprire Plugin > Aggiungi plugin > Carica plugin.
4. Caricare lo ZIP 2.4.0 e sostituire la versione esistente.
5. Attivare il plugin, se WordPress lo richiede.
6. Aprire Studio > Anagrafica Studio e usare Ripara / Verifica Tabelle DB.
7. Aprire Impostazioni > Permalink e premere Salva modifiche, senza cambiare struttura.
8. Verificare l'indirizzo https://dominio.ext/studio-app/.
9. Accedere con un account Professionista o Amministratore.
10. Provare ricerca paziente, modifica Profilo Clinico, salvataggio Anamnesi e nuova Seduta.
11. Verificare che Documenti e Contabilita siano consultabili in sola lettura.
12. Installare la PWA dal browser oppure aggiungerla alla schermata Home.

## Test tecnici
- /studio-app/ deve mostrare il login dedicato.
- /studio-app/manifest.webmanifest deve restituire JSON.
- /studio-app/service-worker.js deve restituire JavaScript.
- /wp-json/studio/v1/dashboard deve essere accessibile solo dopo autenticazione.
- In assenza di rete non devono apparire dati clinici memorizzati.

## Ripristino
In caso di problemi, reinstallare lo ZIP 2.3.1. La 2.4.0 non modifica lo schema dei dati clinici esistenti.
