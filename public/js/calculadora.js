// ==================================================
// ========== CALCULADORA DÓLAR API ================
// ==================================================

let tasaActual = 0;
let calculadoraInicializada = false;

function obtenerTasaDesdeDataAttribute() {
    const calculadoraElement = document.querySelector(".calculator-mini");
    if (calculadoraElement && calculadoraElement.dataset.tasaDolar) {
        const tasa = parseFloat(calculadoraElement.dataset.tasaDolar);
        return tasa;
    }
    return 0;
}

function limpiarFormato(valorFormateado) {
    if (valorFormateado === "" || valorFormateado === null) return 0;

    let valorStr = valorFormateado.toString();
    let sinPuntos = valorStr.replace(/\./g, "");
    let numeroLimpio = sinPuntos.replace(/,/g, ".");
    let numero = parseFloat(numeroLimpio);

    if (isNaN(numero)) return 0;
    return numero;
}

function formatearNumero(numero, decimales = 4) {
    if (
        numero === "" ||
        numero === null ||
        numero === undefined ||
        isNaN(numero)
    ) {
        return "0,00";
    }
    return numero.toLocaleString("es-VE", {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales,
    });
}

function formatearInput(input) {
    let valor = input.value;
    let cursorPos = input.selectionStart;
    let numeros = valor.replace(/\./g, "").replace(/,/g, ".");
    let partes = numeros.split(".");
    let parteEntera = partes[0];
    let parteDecimal = partes[1] !== undefined ? "," + partes[1] : "";

    if (parteEntera !== "") {
        parteEntera = parteEntera.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    let nuevoValor = parteEntera + parteDecimal;

    if (nuevoValor !== valor) {
        input.value = nuevoValor;
        let nuevaPos = cursorPos + (nuevoValor.length - valor.length);
        input.setSelectionRange(Math.max(0, nuevaPos), Math.max(0, nuevaPos));
    }
}

function inicializarCalculadora() {
    if (calculadoraInicializada) {
        return;
    }

    tasaActual = obtenerTasaDesdeDataAttribute();

    if (tasaActual === 0) {
        return;
    }

    const usdInput = document.getElementById("usdAmount");
    const bsInput = document.getElementById("bsAmount");
    const resultBsDiv = document.getElementById("resultBs");
    const resultUsdDiv = document.getElementById("resultUsd");

    if (!usdInput || !bsInput || !resultBsDiv || !resultUsdDiv) {
        return;
    }

    function convertirUSDaBs() {
        let valorUSD = limpiarFormato(usdInput.value);
        let resultadoBs = valorUSD * tasaActual;
        resultBsDiv.innerHTML = `Bs. ${formatearNumero(resultadoBs, 2)}`;
    }

    function convertirBsaUSD() {
        let valorBs = limpiarFormato(bsInput.value);
        let resultadoUSD = valorBs / tasaActual;
        if (isNaN(resultadoUSD)) resultadoUSD = 0;
        let decimales = resultadoUSD < 0.01 ? 6 : 4;
        resultUsdDiv.innerHTML = `$ ${formatearNumero(resultadoUSD, decimales)}`;
    }

    // Limpiar todo
    window.limpiarTodo = function () {
        usdInput.value = "1";
        bsInput.value = "";
        convertirUSDaBs();
        resultUsdDiv.innerHTML = `$ 0,0000`;
    };

    // Eventos
    usdInput.addEventListener("input", function () {
        formatearInput(usdInput);
        convertirUSDaBs();
    });

    usdInput.addEventListener("blur", function () {
        if (usdInput.value === "" || usdInput.value === "-") {
            usdInput.value = "1";
            convertirUSDaBs();
        }
    });

    bsInput.addEventListener("input", function () {
        formatearInput(bsInput);
        convertirBsaUSD();
    });

    bsInput.addEventListener("blur", function () {
        if (bsInput.value === "" || bsInput.value === "-") {
            bsInput.value = "";
            resultUsdDiv.innerHTML = `$ 0,0000`;
        }
    });

    function validarTecla(e) {
        const char = String.fromCharCode(e.which);
        if (
            !/[\d,]/.test(char) &&
            e.which !== 8 &&
            e.which !== 0 &&
            e.which !== 46
        ) {
            e.preventDefault();
        }
        if (char === "," && e.target.value.includes(",")) {
            e.preventDefault();
        }
    }

    usdInput.addEventListener("keypress", validarTecla);
    bsInput.addEventListener("keypress", validarTecla);

    // Inicializar valores
    usdInput.value = "1";
    bsInput.value = "";
    convertirUSDaBs();
    resultUsdDiv.innerHTML = `$ 0,0000`;

    calculadoraInicializada = true;
}

// Auto-inicializar
document.addEventListener("DOMContentLoaded", function () {
    setTimeout(() => {
        inicializarCalculadora();
    }, 100);
});
