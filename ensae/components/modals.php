

  <style>
  @import "../assets/css/profil.css";
</style>


  <!-- Modal pour démarrer les votes -->
  <div id="startVotesModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" id="closeStartVotes">&times;</span>
      <div id="step1">
        <div class="form-group">
          <label for="voteType">Type d'élection</label>
          <select id="voteType">
            <option value="" selected disabled>Choisir un type</option>
            <option value="aes">AES</option>
            <option value="club">Club</option>
            <option value="classe">Classe</option>
          </select>
        </div>
        <div class="form-actions">
          <button class="admin-btn" id="nextToDates">Suivant</button>
        </div>
      </div>
      <div id="step2" style="display:none;">
        <div class="form-group">
          <label for="startVote">Date et heure de début</label>
          <input type="datetime-local" id="startVote">
        </div>
        <div class="form-group">
          <label for="endVote">Date et heure de fin</label>
          <input type="datetime-local" id="endVote">
        </div>
        <div class="form-actions">
          <button class="admin-btn danger" id="cancelVoteModal">Annuler</button>
          <button class="admin-btn" id="validateVoteModal">Valider</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal pour démarrer les candidatures -->
  <div id="startCandModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" id="closeStartCand">&times;</span>
      <div id="candStep1">
        <div class="form-group">
          <label for="candType">Catégorie</label>
          <select id="candType">
            <option value="" selected disabled>Choisir une catégorie</option>
            <option value="aes">AES</option>
            <option value="club">Club</option>
            <option value="classe">Classe</option>
          </select>
        </div>
        <div class="form-actions">
          <button class="admin-btn" id="candNextToDate">Suivant</button>
        </div>
      </div>
      <div id="candStep2" style="display:none;">
        <div class="form-group">
          <label for="startCandDate">Date et heure de début</label>
          <input type="datetime-local" id="startCandDate">
        </div>
        <div class="form-group">
          <label for="endCandDate">Date et heure de fin</label>
          <input type="datetime-local" id="endCandDate">
        </div>
        <div class="form-actions">
          <button class="admin-btn danger" id="cancelCandModal">Annuler</button>
          <button class="admin-btn" id="validateCandModal">Valider</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal pour fermer une session -->
  <div id="closeSessionModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" id="closeCloseSession">&times;</span>
      <div class="form-group">
        <label for="closeSessionCategory">Catégorie</label>
        <select id="closeSessionCategory"></select>
      </div>
      <div class="form-actions">
        <button class="admin-btn danger" id="cancelCloseSession">Annuler</button>
        <button class="admin-btn" id="validateCloseSession">Valider</button>
      </div>
    </div>
  </div>
