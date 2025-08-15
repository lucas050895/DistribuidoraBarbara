document.addEventListener("DOMContentLoaded", function () {
    const buscador = document.getElementById("buscador");
    const filtroRubro = document.getElementById("filtroRubro");
    const grupos = document.querySelectorAll(".grupo-rubro");
    const titulosRubros = document.querySelectorAll(".rubro-titulo");

    if (!buscador || !filtroRubro) return;

    function filtrar() {
        const texto = buscador.value.trim().toLowerCase();
        const rubroSeleccionado = filtroRubro.value;

        grupos.forEach(grupo => {
            const rubro = grupo.getAttribute("data-rubro");
            const filas = grupo.querySelectorAll("table tr:not(:first-child)");
            let algunaVisible = false;

            filas.forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                const coincideTexto = texto === "" || textoFila.includes(texto);
                const coincideRubro = rubroSeleccionado === "todos" || rubroSeleccionado === rubro;

                let mostrar = false;
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

            grupo.style.display = algunaVisible ? "" : "none";
        });

        titulosRubros.forEach(titulo => {
            const rubro = titulo.getAttribute("data-rubro");
            const grupo = document.querySelector(".grupo-rubro[data-rubro='" + rubro + "']");
            titulo.style.display = grupo && grupo.style.display !== "none" ? "" : "none";
        });
    }

    buscador.addEventListener("input", filtrar);
    filtroRubro.addEventListener("change", function() {
        buscador.value = "";
        filtrar();
    });

    filtrar();
});