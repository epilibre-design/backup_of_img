document.addEventListener('DOMContentLoaded', function () {
    var infoZone     = document.getElementById('backup-img-zone-info');
    var progressZone = document.getElementById('backup-img-zone-progress');
    var btn          = document.getElementById('backup-img-btn');

    if (!progressZone) { return; }

    var bar       = document.getElementById('backup-img-bar');
    var statusEl  = document.getElementById('backup-img-status');
    var percentEl = document.getElementById('backup-img-percent');
    var apiBase   = progressZone.dataset.api;
    var doneMsg   = progressZone.dataset.done || 'Sauvegarde terminée.';
    var timer     = null;
    var spinnerStopped = false;
    var boite     = progressZone.closest('.box');

    function showProgress() {
        if (btn) { btn.style.display = 'none'; }
        progressZone.style.display = '';
        if (boite && typeof jQuery !== 'undefined') {
            jQuery(boite).animateLoading();
        }
    }

    function stopSpinner() {
        if (!spinnerStopped && typeof jQuery !== 'undefined') {
            if (boite) { jQuery(boite).endLoading(true); }
            spinnerStopped = true;
        }
    }

    function poll(hash) {
        fetch(apiBase + '&hash=' + encodeURIComponent(hash), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var pct = data.percent || 0;

                if (data.state === 'pending') {
                    statusEl.className = 'notice';
                } else if (data.state === 'running') {
                    stopSpinner();
                    bar.value = pct;
                    percentEl.textContent = pct + ' %';
                    statusEl.className = 'notice';
                } else if (data.state === 'done') {
                    clearInterval(timer);
                    stopSpinner();
                    progressZone.style.display = 'none';
                    bar.value = 0;
                    percentEl.textContent = '0 %';
                    statusEl.className = 'notice';
                    statusEl.textContent = '';
                    spinnerStopped = false;
                    if (infoZone) {
                        var msgOk = document.createElement('p');
                        msgOk.className = 'success';
                        msgOk.textContent = doneMsg;
                        infoZone.insertBefore(msgOk, infoZone.firstChild);
                        setTimeout(function () {
                            if (msgOk.parentNode) { msgOk.parentNode.removeChild(msgOk); }
                        }, 5000);
                    }
                    if (btn) { btn.style.display = ''; }
                    if (typeof ajaxReload === 'function') {
                        ajaxReload('backup_img_liste');
                        ajaxReload('backup_img_taille');
                    } else {
                        window.location.reload();
                    }
                } else if (data.state === 'error') {
                    clearInterval(timer);
                    stopSpinner();
                    statusEl.className = 'error';
                    statusEl.textContent = data.error || 'Erreur inconnue.';
                    if (btn) { btn.style.display = ''; }
                }
            })
            .catch(function () { /* réseau — réessai au prochain tick */ });
    }

    function startPolling(hash) {
        poll(hash);
        timer = setInterval(function () { poll(hash); }, 3000);
    }

    // Cas 1 : page chargée avec ?job=HASH (redirection PHP ou rechargement en cours de sauvegarde)
    var existingHash = progressZone.dataset.hash;
    if (existingHash) {
        showProgress();
        startPolling(existingHash);
    }

    // Suppression : confirmation + spinner sur la cellule action avant navigation
    document.addEventListener('click', function (e) {
        var lien = e.target.closest('[data-confirm]');
        if (!lien) { return; }
        e.preventDefault();
        if (!confirm(lien.dataset.confirm)) { return; }
        var td = lien.closest('td');
        if (td) {
            if (typeof jQuery !== 'undefined') {
                jQuery(td).animateLoading();
            } else {
                lien.style.opacity = '0.4';
                lien.style.pointerEvents = 'none';
            }
        }
        window.location.href = lien.href;
    });

    // Cas 2 : clic sur le bouton (flux JS sans rechargement de page)
    if (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            showProgress();
            fetch(btn.href, { credentials: 'same-origin' })
                .then(function (r) {
                    var url    = new URL(r.url);
                    var hash   = url.searchParams.get('job');
                    var erreur = url.searchParams.get('erreur');
                    if (hash) {
                        startPolling(hash);
                    } else if (erreur) {
                        // Erreur serveur : rechargement pour afficher le message PHP traduit
                        window.location.href = r.url;
                    } else {
                        stopSpinner();
                        progressZone.style.display = 'none';
                        statusEl.className   = 'error';
                        statusEl.textContent = 'Erreur lors du démarrage de la sauvegarde.';
                        if (btn) { btn.style.display = ''; }
                    }
                })
                .catch(function () {
                    stopSpinner();
                    progressZone.style.display = 'none';
                    statusEl.className   = 'error';
                    statusEl.textContent = 'Erreur réseau.';
                    if (btn) { btn.style.display = ''; }
                });
        });
    }
});
