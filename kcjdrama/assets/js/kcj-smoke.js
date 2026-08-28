/**
 * Ambient rising smoke for editorial interiors.
 * Library: bijection/smoke.js. No mouse trail. Not the home hero.
 */
(function () {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        return;
    }
    if (navigator.connection && navigator.connection.saveData) {
        return;
    }
    if (window.matchMedia("(max-width: 699px)").matches) {
        return;
    }
    if (typeof SmokeMachine !== "function" && typeof smokemachine !== "function") {
        return;
    }

    var page = document.querySelector(".kcj-page");
    if (!page || page.classList.contains("kcj-shop")) {
        return;
    }

    var canvas = document.createElement("canvas");
    canvas.className = "kcj-smoke";
    canvas.setAttribute("aria-hidden", "true");
    page.insertBefore(canvas, page.firstChild);

    var ctx = canvas.getContext("2d");
    if (!ctx) {
        canvas.remove();
        return;
    }

    var mirror = page.classList.contains("kcj-page--mirror");
    var color = mirror ? [255, 184, 232] : [72, 28, 42];
    var factory = window.SmokeMachine || window.smokemachine;
    var machine = factory(ctx, color);
    var running = false;

    function size() {
        var w = page.clientWidth;
        var h = Math.max(page.scrollHeight, page.clientHeight);
        if (canvas.width !== w) {
            canvas.width = w;
        }
        if (canvas.height !== h) {
            canvas.height = h;
        }
    }

    function viewSpan() {
        var rect = page.getBoundingClientRect();
        var top = Math.max(0, -rect.top);
        var bot = Math.min(canvas.height, window.innerHeight - rect.top);
        return { top: top, bot: bot };
    }

    function markBox() {
        var el = document.querySelector(".kcj-mini-mark");
        if (!el) {
            return null;
        }
        var pr = page.getBoundingClientRect();
        var r = el.getBoundingClientRect();
        return {
            left: r.left - pr.left,
            width: Math.max(r.width, 96),
            bottom: r.bottom - pr.top
        };
    }

    var puff = {
        minVy: -0.16,
        maxVy: -0.08,
        minVx: -0.012,
        maxVx: 0.012,
        minLifetime: 3500,
        maxLifetime: 7000,
        minScale: 0.2,
        maxScale: 0.6,
        minFinalScale: 5.5,
        maxFinalScale: 9
    };

    function chimneys() {
        var mark = markBox();
        var v = viewSpan();
        if (!mark || v.bot - v.top < 48) {
            return [];
        }
        var y = mark.bottom + 10;
        return [
            { x: mark.left + mark.width * 0.28, y: y },
            { x: mark.left + mark.width * 0.62, y: y + 8 }
        ];
    }

    function spawn(n) {
        var srcs = chimneys();
        if (!srcs.length) {
            return;
        }
        var src = srcs[Math.floor(Math.random() * srcs.length)];
        machine.addSmoke(src.x + (Math.random() * 10 - 5), src.y, n, puff);
    }

    function seed() {
        var srcs = chimneys();
        var s, i;
        for (s = 0; s < srcs.length; s++) {
            for (i = 0; i < 2; i++) {
                machine.addSmoke(
                    srcs[s].x + (Math.random() * 8 - 4),
                    srcs[s].y - i * 70,
                    1,
                    puff
                );
            }
        }
    }

    function go() {
        if (running) {
            return;
        }
        running = true;
        machine.setPreDrawCallback(function () {
            spawn(0.2);
        });
        machine.start();
    }

    function halt() {
        if (!running) {
            return;
        }
        running = false;
        machine.setPreDrawCallback(function () {});
        machine.stop();
    }

    function menuOpen() {
        var btn = document.querySelector(".kcj-burger");
        return btn && btn.getAttribute("aria-expanded") === "true";
    }

    function sync() {
        if (document.hidden || menuOpen()) {
            halt();
        } else {
            go();
        }
    }

    size();
    seed();
    sync();

    window.addEventListener("resize", size, { passive: true });
    document.addEventListener("visibilitychange", sync);
    var burger = document.querySelector(".kcj-burger");
    if (burger) {
        burger.addEventListener("click", function () {
            window.setTimeout(sync, 0);
        });
    }
})();
