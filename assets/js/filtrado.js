(function() {
    // EVITA DOBLE EJECUCIÓN SI EL ARCHIVO SE INCLUYE DOS VECES
    if (window.__FILTRO_RUBROS_INIT__) return;
    window.__FILTRO_RUBROS_INIT__ = true;

    function normalizarTexto(str) {
        // MINÚSCULAS + SIN TILDES PARA BUSCAR MEJOR
        return (str || "")
            .toLowerCase()
            .normalize('NFD')
            .replace(/\p{Diacritic}/gu, '');
    }

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function() {
        const buscador = document.getElementById("buscador");
        const filtroRubro = document.getElementById("filtroRubro");

        // TITULOS DE RUBRO: ACEPTA <h3 data-rubro> O .rubro-titulo
        const titulosRubros = Array.from(document.querySelectorAll("h3[data-rubro], .rubro-titulo"));

        // GRUPOS DE RUBRO
        const grupos = Array.from(document.querySelectorAll(".grupo-rubro"));

        // DEBUG BÁSICO EN PRODUCCIÓN
        if (!buscador || !filtroRubro) {
            console.warn("[filtrado] No se encontró #buscador o #filtroRubro");
            return;
        }
        if (grupos.length === 0) {
            console.warn("[filtrado] No se encontraron .grupo-rubro (¿la ruta del JS está bien? ¿404?)");
        }

        function filtrar() {
            const texto = normalizarTexto(buscador.value.trim());
            const rubroSeleccionado = (filtroRubro.value || "").trim();

            grupos.forEach(grupo => {
                const rubroAttr = (grupo.dataset && grupo.dataset.rubro) || grupo.getAttribute("data-rubro") || "";
                const rubro = rubroAttr.trim();

                // TOMAMOS TODAS LAS FILAS, EXCEPTO LAS DEL THEAD
                const filas = Array.from(grupo.querySelectorAll("tr")).filter(tr => !tr.closest("thead"));

                let algunaVisible = false;

                filas.forEach(fila => {
                    const textoFila = normalizarTexto(fila.textContent || "");
                    const coincideTexto  = (texto === "" || textoFila.includes(texto));
                    const coincideRubro  = (rubroSeleccionado === "todos" || rubroSeleccionado === rubro);

                    // LÓGICA: SI HAY TEXTO -> FILTRA POR TEXTO; SI NO, FILTRA POR RUBRO; SINO MUESTRA TODO
                    let mostrar;
                    if (texto !== "") {
                        mostrar = coincideTexto;
                    } else if (rubroSeleccionado !== "todos") {
                        mostrar = coincideRubro;
                    } else {
                        mostrar = true;
                    }

                    fila.style.display = mostrar ? "" : "none";
                    if (mostrar) algunaVisible = true;
                });

                // OCULTA/MUESTRA EL BLOQUE DEL RUBRO SEGÚN SI TIENE FILAS VISIBLES
                grupo.style.display = algunaVisible ? "" : "none";
            });

            // SINCRONIZA LOS TÍTULOS DE RUBRO CON SUS GRUPOS
            titulosRubros.forEach(titulo => {
                const rubroAttr = (titulo.dataset && titulo.dataset.rubro) || titulo.getAttribute("data-rubro") || "";
                const rubro = rubroAttr.trim();
                const grupo = document.querySelector(".grupo-rubro[data-rubro=\"" + CSS.escape(rubro) + "\"]");
                const visible = grupo && grupo.style.display !== "none";
                titulo.style.display = visible ? "" : "none";
            });
        }

        buscador.addEventListener("input", filtrar);
        filtroRubro.addEventListener("change", function() {
            buscador.value = "";
            filtrar();
        });

        // PRIMERA EJECUCIÓN
        filtrar();

        // LOG PARA VER QUE EL ARCHIVO CARGÓ EN PRODUCCIÓN
        console.log("[filtrado] listo: grupos=", grupos.length, "titulos=", titulosRubros.length);
    });
})();
