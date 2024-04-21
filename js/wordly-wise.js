(() => {
  const getTranslation = async (bid, lang) => {
    const nonce = wordlyWiseAjax.nonce;
    const container = document.querySelector(`[data-blockid="${bid}"]`);
    container.classList.add("wordly-wise-loading");
    const result = await fetch(wordlyWiseAjax.ajaxUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body:
        "action=translate_post&bid=" +
        encodeURIComponent(bid) +
        "&lang=" +
        encodeURIComponent(lang) +
        "&postId=" +
        encodeURIComponent(wordlyWiseAjax.postId) +
        "&_ajax_nonce=" +
        encodeURIComponent(nonce),
    })
      .then(function (response) {
        if (response.ok) {
          return response.json();
        } else {
          throw new Error("Error: " + response.status);
        }
      })
      .then(function (data) {
        const translatedContent = data.data.content;
        if (container) {
          const oldWordlyWiseTransDiv =
            container.querySelector(".wordly-wise-trans");

          if (oldWordlyWiseTransDiv) {
            oldWordlyWiseTransDiv.remove();
          }

          container.innerHTML += `<div class="wordly-wise-trans">${translatedContent}</div>`;
        }
      })
      .catch(function (error) {
        console.error(error);
      })
      .finally(() => {
        container.classList.remove("wordly-wise-loading");
      });

    return result;
  };

  document.addEventListener("DOMContentLoaded", function () {
    const lang = navigator.language;
    const options = ['<option value="">your language</option>'].concat(
      Object.keys(wordlyWiseAjax.enabledLanguages).map((key) => {
        const checked = key === lang ? "selected" : "";
        return `<option value="${key}" checked=${checked}>${wordlyWiseAjax.enabledLanguages[key]}</option>`;
      }),
    );
    const svg = `<svg t="1713541080902" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="1491" width="24" height="24"><path d="M863.288889 861.866667c22.755556 0 38.4-24.177778 29.866667-45.511111L743.822222 465.066667c-14.222222-34.133333-64-34.133333-78.222222 0L516.266667 816.355556c-8.533333 21.333333 7.111111 45.511111 29.866666 45.511111 12.8 0 25.6-8.533333 29.866667-19.911111l38.4-96.711112h179.2l38.4 96.711112c5.688889 11.377778 17.066667 19.911111 31.288889 19.911111zM637.155556 686.933333l66.844444-169.244444 66.844444 169.244444h-133.688888zM583.111111 291.555556h85.333333c15.644444 0 28.444444-12.8 28.444445-28.444445s-12.8-28.444444-28.444445-28.444444H440.888889v-44.088889c0-15.644444-12.8-28.444444-28.444445-28.444445s-28.444444 12.8-28.444444 28.444445v44.088889H156.444444c-15.644444 0-28.444444 12.8-28.444444 28.444444s12.8 28.444444 28.444444 28.444445h366.933334c-9.955556 32.711111-22.755556 64-38.4 95.288888-19.911111 38.4-45.511111 72.533333-73.955556 103.822223h-5.688889c-9.955556-11.377778-29.866667-31.288889-51.2-62.577778-8.533333-12.8-17.066667-25.6-24.177777-39.822222-5.688889-9.955556-14.222222-15.644444-25.6-15.644445-21.333333 0-35.555556 22.755556-25.6 42.666667 8.533333 15.644444 17.066667 31.288889 27.022222 45.511111 19.911111 29.866667 38.4 49.777778 52.622222 65.422222l8.533333 8.533334-157.866666 159.288888c-11.377778 11.377778-11.377778 28.444444 0 39.822223 11.377778 11.377778 28.444444 11.377778 39.822222 0l157.866667-157.866667c24.177778 25.6 51.2 52.622222 79.644444 79.644444 14.222222 14.222222 39.822222 8.533333 46.933333-9.955555 4.266667-9.955556 1.422222-22.755556-7.111111-31.288889-28.444444-27.022222-54.044444-54.044444-78.222222-79.644444 34.133333-36.977778 64-78.222222 88.177778-122.311112 19.911111-38.4 35.555556-78.222222 46.933333-120.888888z" p-id="1492"></path></svg>`;
    document
      .querySelector(".entry-content")
      .insertAdjacentHTML(
        "afterbegin",
        `<div class="wordly-wise-translation-bar">${svg} Translate to<select class="wordly-wise-translation-button">${options.join(
          "",
        )}</select></div>`,
      );

    document
      .querySelector(".wordly-wise-translation-button")
      .addEventListener("change", function () {
        const selectedLanguage = this.value;
        enableTranslation(selectedLanguage);
      });
  });

  function enableTranslation(lang) {
    [...document.querySelectorAll(".wordly-wise-translated")].forEach((x) =>
      x.classList.remove("wordly-wise-translated"),
    );

    const paragraphs = document.querySelectorAll(
      ".entry-content [data-blockid]:not(.wordly-wise-translated)",
    );

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const paragraph = entry.target;
          if (paragraph.classList.contains("wordly-wise-translated")) {
            return;
          }

          const bid = paragraph.getAttribute("data-blockid");

          paragraph.classList.add("wordly-wise-translated");
          getTranslation(bid, lang);
        }
      });
    });

    paragraphs.forEach((paragraph) => {
      observer.observe(paragraph);
    });
  }
})();
