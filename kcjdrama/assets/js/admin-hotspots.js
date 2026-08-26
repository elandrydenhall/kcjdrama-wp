(function () {
    var dataInput = document.getElementById("kcj-hotspots-json");
    var stage = document.getElementById("kcj-editor-stage");
    if (!dataInput || !stage) {
        return;
    }

    var img = stage.querySelector("img");
    var list = document.getElementById("kcj-editor-list");
    var fields = document.getElementById("kcj-editor-fields");
    var viewport = document.getElementById("kcj-editor-viewport");
    var zoomInput = document.getElementById("kcj-zoom");
    var zoomLabel = document.getElementById("kcj-zoom-label");
    var expandBtn = document.getElementById("kcj-expand");

    var zoom = parseInt(sessionStorage.getItem("kcjZoomFit") || "100", 10);
    var expanded = sessionStorage.getItem("kcjExpand") === "1";
    if (isNaN(zoom)) {
        zoom = 100;
    }
    var presets = {
        logo: { href: val("kcj-preset-home"), label: "kcjdrama" },
        soft: { href: val("kcj-preset-soft"), label: "Enter Soft" },
        mirror: { href: val("kcj-preset-mirror"), label: "Enter Mirror" },
        custom: { href: "", label: "Hotspot" }
    };

    var hotspots = parse(dataInput.value);
    var selected = hotspots.length ? 0 : -1;
    var drag = null;

    function val(id) {
        var el = document.getElementById(id);
        return el ? el.value : "";
    }

    function parse(raw) {
        try {
            var data = JSON.parse(raw || "[]");
            return Array.isArray(data) ? data : [];
        } catch (e) {
            return [];
        }
    }

    function uid() {
        return "s" + Math.random().toString(36).slice(2, 8);
    }

    function clamp(n, min, max) {
        return Math.max(min, Math.min(max, n));
    }

    function persist() {
        hotspots.forEach(function (h) {
            h.x = roundPct(h.x);
            h.y = roundPct(h.y);
            h.w = roundPct(h.w);
            h.h = roundPct(h.h);
        });
        dataInput.value = JSON.stringify(hotspots);
    }

    function roundPct(n) {
        return Math.round((parseFloat(n) || 0) * 100) / 100;
    }

    function imagePointFromClient(clientX, clientY) {
        if (!img) {
            return { x: 50, y: 50 };
        }
        var rect = img.getBoundingClientRect();
        return {
            x: ((clientX - rect.left) / Math.max(rect.width, 1)) * 100,
            y: ((clientY - rect.top) / Math.max(rect.height, 1)) * 100
        };
    }

    function viewportFocus() {
        if (!viewport) {
            return { x: 50, y: 50 };
        }
        var vr = viewport.getBoundingClientRect();
        return imagePointFromClient(vr.left + vr.width / 2, vr.top + vr.height / 2);
    }

    function restoreFocus(focus) {
        if (!viewport || !img || !focus) {
            return;
        }
        var ir = img.getBoundingClientRect();
        var vr = viewport.getBoundingClientRect();
        var imgLeft = viewport.scrollLeft + (ir.left - vr.left);
        var imgTop = viewport.scrollTop + (ir.top - vr.top);
        viewport.scrollLeft = imgLeft + (focus.x / 100) * ir.width - viewport.clientWidth / 2;
        viewport.scrollTop = imgTop + (focus.y / 100) * ir.height - viewport.clientHeight / 2;
    }

    function fitWidthPx() {
        if (!viewport || !img || !img.naturalWidth) {
            return 800;
        }
        var padX = 48;
        var padY = 96;
        var availW = Math.max(viewport.clientWidth - padX, 120);
        var availH = Math.max(viewport.clientHeight - padY, 120);
        var ratio = img.naturalWidth / img.naturalHeight;
        var widthForHeight = availH * ratio;
        return Math.min(availW, widthForHeight);
    }

    function applyZoom(options) {
        var opts = options || {};
        var focus = opts.focus || (opts.skipFocus ? null : viewportFocus());
        zoom = clamp(Math.round(zoom), 25, 400);
        var width = Math.round(fitWidthPx() * (zoom / 100));
        stage.style.setProperty("--kcj-stage-width", width + "px");
        if (zoomInput) {
            zoomInput.value = String(clamp(zoom, 25, 200));
        }
        if (zoomLabel) {
            zoomLabel.textContent = zoom + "%";
        }
        sessionStorage.setItem("kcjZoomFit", String(zoom));
        if (focus) {
            requestAnimationFrame(function () {
                restoreFocus(focus);
            });
        }
    }

    function fitAll() {
        zoom = 100;
        applyZoom({ skipFocus: true });
        requestAnimationFrame(function () {
            if (viewport) {
                viewport.scrollLeft = 0;
                viewport.scrollTop = 0;
            }
        });
    }

    function setExpanded(on) {
        expanded = !!on;
        document.body.classList.toggle("kcj-editor-expanded", expanded);
        if (expandBtn) {
            expandBtn.textContent = expanded ? "Exit expand" : "Expand editor";
        }
        sessionStorage.setItem("kcjExpand", expanded ? "1" : "0");
        requestAnimationFrame(function () {
            fitAll();
        });
    }

    if (img && !img.complete) {
        img.addEventListener("load", function () {
            applyZoom({ skipFocus: true });
        });
    }
    applyZoom({ skipFocus: true });
    setExpanded(expanded);

    if (zoomInput) {
        zoomInput.addEventListener("input", function () {
            zoom = parseInt(this.value, 10);
            applyZoom();
        });
    }
    document.getElementById("kcj-zoom-out").addEventListener("click", function () {
        zoom -= 10;
        applyZoom();
    });
    document.getElementById("kcj-zoom-in").addEventListener("click", function () {
        zoom += 10;
        applyZoom();
    });
    document.getElementById("kcj-zoom-fit").addEventListener("click", function () {
        if (!viewport || !img || !img.naturalWidth) {
            zoom = 100;
        } else {
            var fit = fitWidthPx();
            var wide = Math.max(viewport.clientWidth - 48, 120);
            zoom = Math.round((wide / Math.max(fit, 1)) * 100);
        }
        applyZoom({ skipFocus: true });
    });
    var fitAllBtn = document.getElementById("kcj-zoom-all");
    if (fitAllBtn) {
        fitAllBtn.addEventListener("click", fitAll);
    }
    var jumpTopBtn = document.getElementById("kcj-jump-top");
    if (jumpTopBtn) {
        jumpTopBtn.addEventListener("click", function () {
            restoreFocus({ x: 50, y: 0 });
        });
    }
    if (expandBtn) {
        expandBtn.addEventListener("click", function () {
            setExpanded(!expanded);
        });
    }
    var saveBtn = document.getElementById("kcj-save-hero");
    if (saveBtn) {
        saveBtn.addEventListener("click", function () {
            var publish = document.getElementById("publish");
            if (publish) {
                publish.click();
            }
        });
    }
    if (viewport) {
        viewport.addEventListener(
            "wheel",
            function (event) {
                if (!event.ctrlKey && !event.metaKey) {
                    return;
                }
                event.preventDefault();
                zoom += event.deltaY > 0 ? -10 : 10;
                applyZoom({ focus: imagePointFromClient(event.clientX, event.clientY) });
            },
            { passive: false }
        );
    }
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && expanded) {
            setExpanded(false);
        }
    });

    function pctFromEvent(event) {
        var rect = img.getBoundingClientRect();
        return {
            x: ((event.clientX - rect.left) / rect.width) * 100,
            y: ((event.clientY - rect.top) / rect.height) * 100
        };
    }

    function render() {
        stage.querySelectorAll(".kcj-box").forEach(function (el) {
            el.remove();
        });
        list.innerHTML = "";

        hotspots.forEach(function (h, i) {
            var box = document.createElement("div");
            box.className = "kcj-box" + (i === selected ? " is-selected" : "");
            box.style.left = h.x + "%";
            box.style.top = h.y + "%";
            box.style.width = h.w + "%";
            box.style.height = h.h + "%";
            box.dataset.index = String(i);
            box.innerHTML =
                '<span class="kcj-box-label"></span><i class="kcj-resize" data-resize="1"></i>';
            box.querySelector(".kcj-box-label").textContent = h.label || h.id || "Hotspot";
            stage.appendChild(box);

            var item = document.createElement("li");
            item.textContent = h.label || h.id || "Hotspot";
            item.dataset.index = String(i);
            if (i === selected) {
                item.className = "is-selected";
            }
            list.appendChild(item);
        });

        syncFields();
        persist();
    }

    function syncFields() {
        if (selected < 0 || !hotspots[selected]) {
            fields.hidden = true;
            return;
        }
        fields.hidden = false;
        var h = hotspots[selected];
        document.getElementById("kcj-field-label").value = h.label || "";
        document.getElementById("kcj-field-role").value = h.role || "custom";
        document.getElementById("kcj-field-href").value = h.href || "";
        document.getElementById("kcj-field-x").value = roundPct(h.x);
        document.getElementById("kcj-field-y").value = roundPct(h.y);
        document.getElementById("kcj-field-w").value = roundPct(h.w);
        document.getElementById("kcj-field-h").value = roundPct(h.h);
    }

    function applyRole(h, role) {
        h.role = role;
        if (presets[role]) {
            if (role !== "custom") {
                h.href = presets[role].href;
                if (!h.label || ["kcjdrama", "Enter Soft", "Enter Mirror", "Hotspot"].indexOf(h.label) !== -1) {
                    h.label = presets[role].label;
                }
            }
        }
    }

    document.getElementById("kcj-add-hotspot").addEventListener("click", function () {
        hotspots.push({
            id: uid(),
            role: "custom",
            x: 10,
            y: 10,
            w: 16,
            h: 8,
            href: "",
            label: "Hotspot"
        });
        selected = hotspots.length - 1;
        render();
    });

    document.getElementById("kcj-delete-hotspot").addEventListener("click", function () {
        if (selected < 0) {
            return;
        }
        hotspots.splice(selected, 1);
        selected = hotspots.length ? Math.max(0, selected - 1) : -1;
        render();
    });

    ["label", "href", "x", "y", "w", "h"].forEach(function (name) {
        document.getElementById("kcj-field-" + name).addEventListener("input", function () {
            if (selected < 0) {
                return;
            }
            var h = hotspots[selected];
            if (name === "label" || name === "href") {
                h[name] = this.value;
            } else {
                h[name] = roundPct(clamp(parseFloat(this.value || "0"), 0, 100));
            }
            render();
        });
    });

    document.getElementById("kcj-field-role").addEventListener("change", function () {
        if (selected < 0) {
            return;
        }
        applyRole(hotspots[selected], this.value);
        render();
    });

    list.addEventListener("click", function (event) {
        var item = event.target.closest("li");
        if (!item) {
            return;
        }
        selected = parseInt(item.dataset.index, 10);
        render();
    });

    stage.addEventListener("mousedown", function (event) {
        if (!img) {
            return;
        }
        var box = event.target.closest(".kcj-box");
        if (box) {
            selected = parseInt(box.dataset.index, 10);
            var h = hotspots[selected];
            var p = pctFromEvent(event);
            drag = event.target.getAttribute("data-resize")
                ? { mode: "resize", index: selected, startX: p.x, startY: p.y, orig: Object.assign({}, h) }
                : { mode: "move", index: selected, startX: p.x, startY: p.y, orig: Object.assign({}, h) };
            render();
            event.preventDefault();
            return;
        }

        var start = pctFromEvent(event);
        var next = {
            id: uid(),
            role: "custom",
            x: start.x,
            y: start.y,
            w: 0.5,
            h: 0.5,
            href: "",
            label: "Hotspot"
        };
        hotspots.push(next);
        selected = hotspots.length - 1;
        drag = { mode: "draw", index: selected, startX: start.x, startY: start.y, orig: Object.assign({}, next) };
        render();
        event.preventDefault();
    });

    window.addEventListener("mousemove", function (event) {
        if (!drag || !img) {
            return;
        }
        var p = pctFromEvent(event);
        var h = hotspots[drag.index];
        if (drag.mode === "move") {
            h.x = clamp(drag.orig.x + (p.x - drag.startX), 0, 100 - h.w);
            h.y = clamp(drag.orig.y + (p.y - drag.startY), 0, 100 - h.h);
        } else {
            var x1 = Math.min(drag.startX, p.x);
            var y1 = Math.min(drag.startY, p.y);
            var x2 = Math.max(drag.startX, p.x);
            var y2 = Math.max(drag.startY, p.y);
            h.x = clamp(x1, 0, 99);
            h.y = clamp(y1, 0, 99);
            h.w = clamp(x2 - h.x, 0.5, 100 - h.x);
            h.h = clamp(y2 - h.y, 0.5, 100 - h.y);
        }
        render();
    });

    window.addEventListener("mouseup", function () {
        if (drag && drag.mode === "draw") {
            var drawn = hotspots[drag.index];
            if (drawn && drawn.w < 1.5 && drawn.h < 1.5) {
                hotspots.splice(drag.index, 1);
                selected = hotspots.length ? Math.min(drag.index, hotspots.length - 1) : -1;
                render();
            }
        }
        drag = null;
    });

    var postForm = document.getElementById("post");
    if (postForm) {
        postForm.setAttribute("novalidate", "novalidate");
    }

    render();
})();
