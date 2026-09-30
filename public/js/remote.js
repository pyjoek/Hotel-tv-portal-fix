(function () {
    var LEFT = [37, 4];
    var UP = [38, 19];
    var RIGHT = [39, 5];
    var DOWN = [40, 20];
    var ENTER = [13, 23];
    var BACK = [8, 27, 10009, 461, 10182];

    function has(list, code) { return list.indexOf(code) !== -1; }

    function items() {
        return Array.prototype.filter.call(
            document.querySelectorAll('a.tile, button.focusable, input.focusable, [data-focus]'),
            function (el) { return el.offsetWidth > 0 && el.offsetHeight > 0; }
        );
    }

    function center(el) {
        var r = el.getBoundingClientRect();
        return { x: r.left + r.width / 2, y: r.top + r.height / 2 };
    }

    function nearest(dir) {
        var all = items();
        if (!all.length) return;
        var cur = document.activeElement;
        if (all.indexOf(cur) === -1) {
            all[0].focus();
            return;
        }
        var c = center(cur);
        var best = null;
        var bestScore = Infinity;
        all.forEach(function (el) {
            if (el === cur) return;
            var n = center(el);
            var dx = n.x - c.x;
            var dy = n.y - c.y;
            if (dir === 'left' && dx >= -8) return;
            if (dir === 'right' && dx <= 8) return;
            if (dir === 'up' && dy >= -8) return;
            if (dir === 'down' && dy <= 8) return;
            var primary = (dir === 'left' || dir === 'right') ? Math.abs(dx) : Math.abs(dy);
            var secondary = (dir === 'left' || dir === 'right') ? Math.abs(dy) : Math.abs(dx);
            var score = primary + secondary * 2.4;
            if (score < bestScore) {
                bestScore = score;
                best = el;
            }
        });
        if (best) {
            best.focus();
            best.scrollIntoView({ block: 'center', inline: 'nearest' });
        }
    }

    document.addEventListener('keydown', function (e) {
        var code = e.keyCode || e.which;
        var player = document.body.getAttribute('data-screen') === 'player';
        var back = document.querySelector('a.back');

        if (has(BACK, code) || e.key === 'BrowserBack' || e.key === 'GoBack') {
            if (back) {
                e.preventDefault();
                window.location.href = back.getAttribute('href');
            }
            return;
        }

        if (player) {
            var box = document.querySelector('.player');
            if ((has(UP, code) || e.key === 'ArrowUp') && box && box.dataset.prev) {
                e.preventDefault();
                window.location.href = box.dataset.prev;
                return;
            }
            if ((has(DOWN, code) || e.key === 'ArrowDown') && box && box.dataset.next) {
                e.preventDefault();
                window.location.href = box.dataset.next;
                return;
            }
            if (has(ENTER, code) || e.key === 'Enter') {
                var video = document.getElementById('video');
                if (video) {
                    e.preventDefault();
                    if (video.paused) video.play(); else video.pause();
                }
                return;
            }
            if ((has(LEFT, code) || e.key === 'ArrowLeft') && back) {
                e.preventDefault();
                back.focus();
            }
            return;
        }

        if (e.key === 'ArrowLeft' || has(LEFT, code)) { e.preventDefault(); nearest('left'); }
        else if (e.key === 'ArrowRight' || has(RIGHT, code)) { e.preventDefault(); nearest('right'); }
        else if (e.key === 'ArrowUp' || has(UP, code)) { e.preventDefault(); nearest('up'); }
        else if (e.key === 'ArrowDown' || has(DOWN, code)) { e.preventDefault(); nearest('down'); }
        else if ((e.key === 'Enter' || has(ENTER, code)) && document.activeElement && document.activeElement.click && document.activeElement.tagName !== 'INPUT') {
            e.preventDefault();
            document.activeElement.click();
        }
    });

    window.addEventListener('load', function () {
        var start = document.querySelector('.preferred') || items()[0];
        if (start) start.focus();
    });
})();
