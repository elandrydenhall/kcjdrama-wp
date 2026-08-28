document.addEventListener("DOMContentLoaded", function () {
  var COLOR_MAP = {
    black: "#111111",
    white: "#f5f2ea",
    bone: "#e8e0d0",
    ash: "#b2b2b2",
    "carbon grey": "#5b5b5b",
    "carbon gray": "#5b5b5b",
    grey: "#8a8a8a",
    gray: "#8a8a8a",
    "sport grey": "#9b9b9b",
    "sport gray": "#9b9b9b",
    "heather grey": "#9a9a9a",
    "heather gray": "#9a9a9a",
    "dark heather": "#4a4a4a",
    "graphite heather": "#5c5f63",
    graphite: "#4d5258",
    navy: "#1a2a4a",
    "navy blue": "#1a2a4a",
    cardinal: "#8C1515",
    crimson: "#9b1b30",
    red: "#b91c1c",
    forest: "#1f4d2e",
    "military green": "#4b5320",
    green: "#2f6b3a",
    khaki: "#c3b091",
    latte: "#c4a484",
    "dusty rose": "#d4a5a5",
    "light pink": "#f3c6d0",
    pink: "#e89bb0",
    "sky blue": "#87b8d9",
    blue: "#2f5f9f",
    "oatmeal heather": "#d8cbb5",
    oatmeal: "#d8cbb5",
    sand: "#cbb994",
    charcoal: "#36454f",
    maroon: "#800000",
    purple: "#5b2c6f",
    gold: "#d4af37",
    yellow: "#e2b93d",
    "yellow haze": "#e2b93d",
    orange: "#d97706",
    brown: "#6b4226",
    "brown savana": "#8d6e4c",
    "ice grey": "#cfd3d6",
    "ice gray": "#cfd3d6",
    cream: "#f6f3ec",
    ivory: "#fffff0",
    natural: "#e9e0cf",
    "vintage black": "#1a1a1a",
  };

  function colorFor(name) {
    if (!name) return null;
    var key = String(name).trim().toLowerCase();
    if (COLOR_MAP[key]) return COLOR_MAP[key];
    var parts = key.split(/[\s/_-]+/);
    for (var i = parts.length - 1; i >= 0; i--) {
      if (COLOR_MAP[parts[i]]) return COLOR_MAP[parts[i]];
    }
    return null;
  }

  function isLightGarmentColor(color) {
    return /white|bone|cream|ivory|natural|oatmeal|sand|pink|sky|latte|khaki|yellow|gold|haze|ash/.test(
      String(color || "").toLowerCase()
    );
  }

  function setPdpMockupGlow(color) {
    var gallery = document.querySelector(".woocommerce-product-gallery");
    if (!gallery) return;
    gallery.classList.toggle("is-light-mockup", isLightGarmentColor(color));
  }

  function applyPdpColorImagePayload(info) {
    if (!info || !info.src) return;
    var gallery = document.querySelector(".woocommerce-product-gallery");
    var imgs = document.querySelectorAll(
      ".woocommerce-product-gallery .woocommerce-product-gallery__image img, .woocommerce-product-gallery img.wp-post-image"
    );
    if (!imgs.length) return;
    var first = imgs[0];
    var already = first.getAttribute("src") === info.src;
    if (gallery && !already) gallery.classList.add("is-swapping");
    imgs.forEach(function (img) {
      img.setAttribute("src", info.src);
      if (info.srcset) img.setAttribute("srcset", info.srcset);
      else img.removeAttribute("srcset");
      if (info.full) {
        img.setAttribute("data-src", info.full);
        img.setAttribute("data-large_image", info.full);
        var link = img.closest("a");
        if (link) link.setAttribute("href", info.full);
      }
      if (gallery && !already) {
        var done = function () {
          gallery.classList.remove("is-swapping");
          img.removeEventListener("load", done);
        };
        img.addEventListener("load", done);
        window.setTimeout(done, 480);
      }
    });
  }

  function enhanceVariationForms() {
    var forms = document.querySelectorAll("form.variations_form");
    forms.forEach(function (form) {
      if (form.getAttribute("data-kcj-swatches") === "1") return;
      form.setAttribute("data-kcj-swatches", "1");

      var rows = form.querySelectorAll("table.variations tr, .variations tr");
      rows.forEach(function (row) {
        var select = row.querySelector("select");
        if (!select || select.options.length < 2) return;

        var labelText = "";
        var labelEl = row.querySelector("label");
        if (labelEl) labelText = (labelEl.textContent || "").toLowerCase();

        var isColor =
          /color|colour/.test(labelText) ||
          /color|colour/.test((select.id || "").toLowerCase()) ||
          /color|colour/.test((select.name || "").toLowerCase());

        var wrap = document.createElement("div");
        wrap.className =
          "kcj-swatches" + (isColor ? " kcj-swatches--color" : " kcj-swatches--size");
        wrap.setAttribute("role", "listbox");
        wrap.setAttribute(
          "aria-label",
          labelEl ? labelEl.textContent.trim() : select.name || "options"
        );

        Array.prototype.forEach.call(select.options, function (opt) {
          if (!opt.value) return;
          var btn = document.createElement("button");
          btn.type = "button";
          btn.className = "kcj-swatch";
          btn.setAttribute("role", "option");
          btn.setAttribute("data-value", opt.value);
          btn.setAttribute(
            "aria-selected",
            select.value === opt.value ? "true" : "false"
          );

          var label = opt.textContent.trim();
          if (isColor) {
            var hex = colorFor(label) || colorFor(opt.value);
            btn.title = label;
            btn.setAttribute("aria-label", label);
            if (hex) {
              btn.classList.add("kcj-swatch--chip");
              btn.style.setProperty("--swatch", hex);
              if (isLightGarmentColor(label)) btn.classList.add("kcj-swatch--light");
              btn.innerHTML =
                '<span class="kcj-swatch-dot" aria-hidden="true"></span><span class="kcj-swatch-label">' +
                label +
                "</span>";
            } else {
              btn.textContent = label;
            }
          } else {
            btn.textContent = label;
          }

          if (select.value === opt.value) btn.classList.add("is-selected");

          btn.addEventListener("click", function () {
            if (select.value === opt.value && !isColor) return;
            if (isColor) {
              var sizeSel =
                form.querySelector('select[name="attribute_pa_size"]') ||
                form.querySelector("#pa_size");
              if (sizeSel && !sizeSel.value) {
                Array.prototype.forEach.call(sizeSel.options, function (o) {
                  if (String(o.value).toLowerCase() === "m") sizeSel.value = o.value;
                });
                if (!sizeSel.value) {
                  Array.prototype.forEach.call(sizeSel.options, function (o) {
                    if (o.value && !sizeSel.value) sizeSel.value = o.value;
                  });
                }
              }
            }
            select.value = opt.value;
            if (window.jQuery) {
              window.jQuery(select).trigger("change");
            } else {
              select.dispatchEvent(new Event("change", { bubbles: true }));
            }
            wrap.querySelectorAll(".kcj-swatch").forEach(function (el) {
              var on = el.getAttribute("data-value") === opt.value;
              el.classList.toggle("is-selected", on);
              el.setAttribute("aria-selected", on ? "true" : "false");
            });
            if (isColor) setPdpMockupGlow(opt.value);
          });
          wrap.appendChild(btn);
        });

        select.classList.add("kcj-native-select");
        select.setAttribute("aria-hidden", "true");
        select.tabIndex = -1;
        select.parentNode.insertBefore(wrap, select.nextSibling);

        select.addEventListener("change", function () {
          wrap.querySelectorAll(".kcj-swatch").forEach(function (el) {
            var on = el.getAttribute("data-value") === select.value;
            el.classList.toggle("is-selected", on);
            el.setAttribute("aria-selected", on ? "true" : "false");
          });
        });
      });
    });
  }

  enhanceVariationForms();

  (function initGlow() {
    var sel =
      document.querySelector('form.variations_form select[name="attribute_pa_color"]') ||
      document.querySelector("form.variations_form #pa_color");
    if (sel && sel.value) setPdpMockupGlow(sel.value);
  })();

  document.body.addEventListener("woocommerce_update_variation_values", enhanceVariationForms);

  if (window.jQuery) {
    window.jQuery(document.body).on("reset_data", function () {
      var slot = document.querySelector("[data-kcj-pdp-price]");
      var tpl = document.querySelector("[data-kcj-pdp-price-default]");
      if (slot && tpl) slot.innerHTML = tpl.innerHTML;
    });
    window.jQuery(document.body).on("found_variation", function (_e, variation) {
      if (variation && variation.image) {
        applyPdpColorImagePayload({
          src: variation.image.src,
          srcset: variation.image.srcset || "",
          full: variation.image.full_src || variation.image.src,
        });
        if (variation.attributes && variation.attributes.attribute_pa_color) {
          setPdpMockupGlow(variation.attributes.attribute_pa_color);
        }
      }
      document.querySelectorAll("form.variations_form").forEach(function (form) {
        form.querySelectorAll("select").forEach(function (select) {
          var wrap = select.parentNode.querySelector(".kcj-swatches");
          if (!wrap) return;
          wrap.querySelectorAll(".kcj-swatch").forEach(function (el) {
            var on = el.getAttribute("data-value") === select.value;
            el.classList.toggle("is-selected", on);
            el.setAttribute("aria-selected", on ? "true" : "false");
          });
        });
      });
    });
  }
});
