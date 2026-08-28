(function () {
    var wall = document.querySelector("[data-kcj-wall]");
    var sentinel = document.querySelector("[data-kcj-wall-sentinel]");
    if (!wall || !sentinel) return;

    var rest = (window.kcjWall && window.kcjWall.rest) || "";
    if (!rest) return;

    var page = parseInt(wall.getAttribute("data-page") || "1", 10) || 1;
    var per = parseInt(wall.getAttribute("data-per") || "12", 10) || 12;
    var rail = wall.getAttribute("data-rail") || "all";
    var mode = wall.getAttribute("data-mode") || "home";
    var color = wall.getAttribute("data-color") || "";
    var size = wall.getAttribute("data-size") || "";
    var orderby = wall.getAttribute("data-orderby") || "";
    var search = wall.getAttribute("data-s") || "";
    var hasMore = wall.getAttribute("data-has-more") === "1";
    var loading = false;
    var emptyEl = document.querySelector(".kcj-wall-empty");
    var shopLink = document.querySelector("[data-kcj-shop-link]");
    var shopLabel = document.querySelector("[data-kcj-shop-link-label]");
    var shopRoot = document.querySelector("[data-kcj-shop-root]");
    var shopKicker = document.querySelector("[data-kcj-shop-kicker]");
    var shopBlurb = document.querySelector("[data-kcj-shop-blurb]");
    var resultCount = document.querySelector(".shop-toolbar-meta .woocommerce-result-count");
    var split = document.getElementById("kcj-shop-split");
    var io = null;

    var shopLabels = {
        soft: "See all Soft in Shop",
        mirror: "See all Mirror in Shop",
        all: "Open full Shop",
    };
    var shopKickers = {
        soft: "Soft Merch",
        mirror: "Mirror Merch",
        all: "Shop the Split",
    };

    function setHasMore(on) {
        hasMore = !!on;
        if (hasMore) sentinel.removeAttribute("hidden");
        else sentinel.setAttribute("hidden", "");
        wall.setAttribute("data-has-more", hasMore ? "1" : "0");
    }

    function wallQuery(pageNum) {
        var q =
            "?rail=" +
            encodeURIComponent(rail) +
            "&page=" +
            pageNum +
            "&per=" +
            per +
            "&mode=" +
            encodeURIComponent(mode);
        if (color) q += "&color=" + encodeURIComponent(color);
        if (size) q += "&size=" + encodeURIComponent(size);
        if (orderby) q += "&orderby=" + encodeURIComponent(orderby);
        if (search) q += "&s=" + encodeURIComponent(search);
        return q;
    }

    function sentinelVisible() {
        var rect = sentinel.getBoundingClientRect();
        return rect.top < (window.innerHeight || 0) + 400 && rect.bottom > -80;
    }

    function fetchWall(pageNum, replace) {
        if (loading) return Promise.resolve();
        loading = true;
        wall.classList.add("is-loading");

        var opts = { credentials: "same-origin" };
        if (window.kcjWall && window.kcjWall.nonce) {
            opts.headers = { "X-WP-Nonce": window.kcjWall.nonce };
        }

        return fetch(rest + wallQuery(pageNum), opts)
            .then(function (r) {
                if (!r.ok) throw new Error("wall " + r.status);
                return r.json();
            })
            .then(function (data) {
                if (!data) return;
                if (replace) {
                    wall.innerHTML = data.html || "";
                } else if (data.html) {
                    wall.insertAdjacentHTML("beforeend", data.html);
                }
                page = pageNum;
                wall.setAttribute("data-page", String(page));
                wall.setAttribute("data-rail", rail);
                setHasMore(!!data.has_more);
                wall.setAttribute("data-total", String(data.total || 0));
                if (resultCount && typeof data.total === "number") {
                    resultCount.textContent =
                        data.total === 1 ? "1 design" : data.total + " designs";
                }
                if (emptyEl) {
                    if ((data.html || "").indexOf("kcj-product") === -1 && pageNum === 1) {
                        emptyEl.hidden = false;
                    } else {
                        emptyEl.hidden = true;
                    }
                }
            })
            .catch(function () {
                /* leave hasMore so a later scroll can retry */
            })
            .finally(function () {
                loading = false;
                wall.classList.remove("is-loading");
            });
    }

    function loadNext() {
        if (loading || !hasMore) return;
        fetchWall(page + 1, false).then(function () {
            if (hasMore && !loading && sentinelVisible()) loadNext();
        });
    }

    function updateToggle(nextRail) {
        document.querySelectorAll(".kcj-rail-toggle a[data-rail]").forEach(function (a) {
            var on = a.getAttribute("data-rail") === nextRail;
            a.classList.toggle("is-active", on);
            if (on) a.setAttribute("aria-current", "page");
            else a.removeAttribute("aria-current");
        });
    }

    function updateShopChrome(nextRail) {
        if (shopLink) {
            var key = nextRail === "soft" || nextRail === "mirror" ? nextRail : "all";
            var href = shopLink.getAttribute("data-" + key);
            if (href) shopLink.setAttribute("href", href);
            if (shopLabel) shopLabel.textContent = shopLabels[key] || shopLabels.all;
        }
        if (shopRoot) {
            shopRoot.setAttribute("data-rail", nextRail);
            shopRoot.className =
                shopRoot.className.replace(/kcj-shop--\w+/g, "").trim() +
                " kcj-shop--catalog kcj-shop--" +
                nextRail;
        }
        if (shopKicker) shopKicker.textContent = shopKickers[nextRail] || shopKickers.all;
        if (shopBlurb && window.kcjShopBlurbs && window.kcjShopBlurbs[nextRail]) {
            shopBlurb.textContent = window.kcjShopBlurbs[nextRail];
        }
    }

    function switchRail(nextRail, href) {
        if (nextRail === rail && !loading) return;
        rail = nextRail;
        page = 0;
        setHasMore(true);
        updateToggle(nextRail);
        updateShopChrome(nextRail);
        if (window.history && href) {
            var clean = href.split("#")[0];
            window.history.replaceState({ rail: nextRail }, "", clean + "#kcj-shop-split");
        }
        // In-place swap — do not scroll; that was jumping people to the hero on reload.
        fetchWall(1, true);
    }

    document.querySelectorAll(".kcj-rail-toggle a[data-rail]").forEach(function (a) {
        a.addEventListener("click", function (e) {
            if (e.defaultPrevented) return;
            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            e.preventDefault();
            switchRail(a.getAttribute("data-rail") || "all", a.href);
        });
    });

    if ("IntersectionObserver" in window) {
        io = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) loadNext();
                });
            },
            { rootMargin: "400px 0px" }
        );
        io.observe(sentinel);
    } else {
        window.addEventListener(
            "scroll",
            function () {
                if (!hasMore || loading) return;
                var rect = sentinel.getBoundingClientRect();
                if (rect.top < window.innerHeight + 400) loadNext();
            },
            { passive: true }
        );
    }

    // If we arrived via #kcj-shop-split (no-JS fallback), jump past the hero once.
    if (split && window.location.hash === "#kcj-shop-split") {
        window.requestAnimationFrame(function () {
            split.scrollIntoView({ block: "start" });
        });
    }
})();
