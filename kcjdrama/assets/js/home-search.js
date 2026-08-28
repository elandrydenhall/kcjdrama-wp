(function () {
    var form = document.querySelector("[data-kcj-home-search]");
    if (!form) return;

    var input = form.querySelector(".kcj-home-search-input");
    var clearBtn = form.querySelector(".kcj-home-search-clear");
    var list = form.querySelector(".kcj-home-suggest");
    var rest = (window.kcjSearch && window.kcjSearch.rest) || "";
    var timer = 0;
    var abort = null;
    var items = [];
    var active = -1;

    function setClear() {
        var on = input.value.length > 0;
        clearBtn.hidden = !on;
    }

    function closeList() {
        list.hidden = true;
        list.innerHTML = "";
        items = [];
        active = -1;
        input.setAttribute("aria-expanded", "false");
        input.removeAttribute("aria-activedescendant");
    }

    function paintActive() {
        var nodes = list.querySelectorAll("[role='option']");
        nodes.forEach(function (el, i) {
            var on = i === active;
            el.setAttribute("aria-selected", on ? "true" : "false");
            el.classList.toggle("is-active", on);
            if (on) input.setAttribute("aria-activedescendant", el.id);
        });
    }

    function render(rows) {
        items = rows || [];
        active = -1;
        if (!items.length) {
            closeList();
            return;
        }
        list.innerHTML = items
            .map(function (row, i) {
                return (
                    '<li role="option" id="kcj-sug-' +
                    i +
                    '" data-i="' +
                    i +
                    '" aria-selected="false">' +
                    '<span class="kcj-home-suggest-title"></span>' +
                    '<span class="kcj-home-suggest-type"></span>' +
                    "</li>"
                );
            })
            .join("");
        list.querySelectorAll("[role='option']").forEach(function (el, i) {
            el.querySelector(".kcj-home-suggest-title").textContent = items[i].title;
            el.querySelector(".kcj-home-suggest-type").textContent = items[i].type;
        });
        list.hidden = false;
        input.setAttribute("aria-expanded", "true");
    }

    function fetchSuggest() {
        var q = input.value.trim();
        if (q.length < 2 || !rest) {
            closeList();
            return;
        }
        if (abort) abort.abort();
        abort = new AbortController();
        fetch(rest + "?q=" + encodeURIComponent(q), { signal: abort.signal })
            .then(function (r) {
                return r.json();
            })
            .then(render)
            .catch(function (err) {
                if (err && err.name === "AbortError") return;
                closeList();
            });
    }

    function go(i) {
        if (i < 0 || i >= items.length || !items[i].url) return;
        window.location.href = items[i].url;
    }

    input.addEventListener("input", function () {
        setClear();
        window.clearTimeout(timer);
        timer = window.setTimeout(fetchSuggest, 180);
    });

    clearBtn.addEventListener("click", function () {
        input.value = "";
        setClear();
        closeList();
        input.focus();
    });

    list.addEventListener("mousedown", function (e) {
        var opt = e.target.closest("[role='option']");
        if (!opt) return;
        e.preventDefault();
        go(parseInt(opt.getAttribute("data-i"), 10));
    });

    input.addEventListener("keydown", function (e) {
        if (list.hidden) {
            if (e.key === "Escape" && input.value) {
                input.value = "";
                setClear();
            }
            return;
        }
        if (e.key === "ArrowDown") {
            e.preventDefault();
            active = (active + 1) % items.length;
            paintActive();
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            active = active <= 0 ? items.length - 1 : active - 1;
            paintActive();
        } else if (e.key === "Enter" && active >= 0) {
            e.preventDefault();
            go(active);
        } else if (e.key === "Escape") {
            closeList();
        }
    });

    document.addEventListener("click", function (e) {
        if (!form.contains(e.target)) closeList();
    });

    setClear();
})();
