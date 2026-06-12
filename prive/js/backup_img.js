document.addEventListener('DOMContentLoaded', function () {
    var container = document.getElementById('backup-img-progress');
    if (!container) return;

    var hash = container.dataset.hash;
    if (!hash) return;

    var apiBase   = container.dataset.api;
    var doneMsg   = container.dataset.done || 'Sauvegarde terminée.';
    var bar       = document.getElementById('backup-img-bar');
    var statusEl  = document.getElementById('backup-img-status');
    var percentEl = document.getElementById('backup-img-percent');

    container.style.display = '';

    function poll() {
        fetch(apiBase + '&hash=' + encodeURIComponent(hash), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var pct = data.percent || 0;
                bar.value = pct;
                percentEl.textContent = pct + ' %';

                if (data.state === 'pending' || data.state === 'running') {
                    statusEl.className = 'notice';
                } else if (data.state === 'done') {
                    clearInterval(timer);
                    bar.value = 100;
                    percentEl.textContent = '100 %';
                    statusEl.className = 'success';
                    statusEl.textContent = doneMsg;
                    // Recharge uniquement la liste via ajaxReload (bloc ajax=backup_img_liste)
                    if (typeof ajaxReload === 'function') {
                        ajaxReload('backup_img_liste');
                    } else {
                        window.location.reload();
                    }
                } else if (data.state === 'error') {
                    clearInterval(timer);
                    statusEl.className = 'error';
                    statusEl.textContent = data.error || 'Erreur inconnue.';
                }
            })
            .catch(function () { /* réseau — réessai au prochain tick */ });
    }

    // Premier appel immédiat pour déclencher le job (fastcgi_finish_request côté serveur)
    poll();
    var timer = setInterval(poll, 3000);
});
