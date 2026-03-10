document.addEventListener('DOMContentLoaded', () => {
    
    // --- MODELO DE DATOS Y ESTADO DE LA APLICACIÓN ---
    let conteoData = null;
    let aliasData = null;
    let selectedAliases = ['Todas'];
    let currentFilter = {};
    let isUpdatingFilters = false;
    let monthsByYear = {};
    
    // NUEVO: Clasificación de loterías por país y horario
    const loteriasAmericanasDia = [
        "Anguilla 10AM", "Florida Tarde", "New York Tarde", 
        "Anguilla 1 PM", "Anguilla Tarde (6 PM)", "King Lottery Dia"
    ];
    
    const loteriasAmericanasNoche = [
        "Florida Noche", "New York Noche", 
        "Anguilla Noche (9 PM)", "King Lottery Noche"
    ];
    
    const loteriasDominicanasDia = [
        "Gana Mas", "La Real", "La Primera Dia", 
        "La Suerte 12:30", "La Suerte 6 PM", "LoteDom"
    ];
    
    const loteriasDominicanasNoche = [
        "Nacional", "Leidsa", "Loteka", "La Primera Noche"
    ];
    
    // Estado de los filtros de país y horario (ahora con 3 posiciones)
    let filtroPais = 'todas'; // 'dominicanas', 'todas' o 'americanas'
    let filtroHorario = 'todas'; // 'dia', 'todas' o 'noche'

    // --- CONSTANTES Y REFERENCIAS AL DOM ---
    const tabla = document.getElementById('tabla-numeros');
    const toggleSwitch = document.getElementById('dataToggle');
    const loadingDiv = document.getElementById('loading');
    const errorDiv = document.getElementById('error');
    const filterStatusDiv = document.getElementById('filterStatus');
    const filterStatusContent = document.getElementById('filterStatusContent');
    const STORAGE_KEY_SWITCH = 'tabla-numeros-switch-state';
    const STORAGE_KEY_ALIASES = 'tabla-numeros-selected-aliases';
    const STORAGE_KEY_FILTERS = 'tabla-numeros-filters-state';
    const STORAGE_KEY_PAIS = 'tabla-numeros-pais-filter';
    const STORAGE_KEY_HORARIO = 'tabla-numeros-horario-filter';
    
    // Controles de Menú Móvil
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    // Controles de Alias
    const aliasDropdownBtn = document.getElementById('aliasDropdownBtn');
    const aliasDropdownContent = document.getElementById('aliasDropdownContent');
    const aliasOptions = document.getElementById('aliasOptions');
    const aliasSelectedText = document.getElementById('aliasSelectedText');
    const clearAliasBtn = document.getElementById('clearAliasBtn');
    const applyAliasBtn = document.getElementById('applyAliasBtn');

    // Controles de Fecha
    const yearFilter = document.getElementById('yearFilter');
    const monthFilter = document.getElementById('monthFilter');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const clearDateFilterBtn = document.getElementById('clearDateFilter');
    
    // Controles de Fecha Única
    const singleDateInput = document.getElementById('singleDate');
    const specificDayBtn = document.getElementById('specificDayBtn');
    const todayBtn = document.getElementById('todayBtn');
    
    // NUEVO: Controles de País y Horario (botones de 3 posiciones)
    const paisSwitch = document.getElementById('paisSwitch');
    const horarioSwitch = document.getElementById('horarioSwitch');
    
    // --- HELPERS ---
    const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

    function crearTabla() {
        tabla.innerHTML = '';
        for (let i = 0; i < 100; i += 10) {
            const thead = document.createElement('thead');
            const filaTh = document.createElement('tr');
            for (let j = i; j < i + 10; j++) {
                const th = document.createElement('th');
                th.textContent = j.toString().padStart(2, '0');
                filaTh.appendChild(th);
            }
            thead.appendChild(filaTh);
            tabla.appendChild(thead);

            const tbody = document.createElement('tbody');
            const filaTd = document.createElement('tr');
            for (let j = i; j < i + 10; j++) {
                const td = document.createElement('td');
                td.id = `num-${j.toString().padStart(2, '0')}`;
                td.textContent = '\u00a0';
                td.classList.add('empty-cell');
                filaTd.appendChild(td);
            }
            tbody.appendChild(filaTd);
            tabla.appendChild(tbody);
        }
    }
    
    function limpiarCeldasTabla() {
        for (let i = 0; i < 100; i++) {
            const celda = document.getElementById(`num-${i.toString().padStart(2, '0')}`);
            if (celda) {
                celda.textContent = '\u00a0';
                celda.className = 'empty-cell';
            }
        }
    }

    function agregarConteos(total, parcial) {
        if (!parcial) return;
        for (const numero in parcial) {
            total[numero] = (total[numero] || 0) + parcial[numero];
        }
    }
    
    function formatDateLong(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString + 'T00:00:00');
        const day = date.getDate();
        const month = MESES[date.getMonth()];
        const year = date.getFullYear();
        return `${day} de ${month} de ${year}`;
    }
    
    // NUEVO: Función para obtener loterías según filtros de país y horario
    function obtenerLoteriasFiltradas() {
        // Si ambos filtros están en "todas", devolver todas las loterías
        if (filtroPais === 'todas' && filtroHorario === 'todas') {
            return Object.values(aliasData); // Todas las loterías
        }
        
        let loterias = [];
        
        // Caso 1: País específico, horario "todas"
        if (filtroPais !== 'todas' && filtroHorario === 'todas') {
            if (filtroPais === 'dominicanas') {
                loterias = [...loteriasDominicanasDia, ...loteriasDominicanasNoche];
            } else { // americanas
                loterias = [...loteriasAmericanasDia, ...loteriasAmericanasNoche];
            }
        }
        // Caso 2: País "todas", horario específico
        else if (filtroPais === 'todas' && filtroHorario !== 'todas') {
            if (filtroHorario === 'dia') {
                loterias = [...loteriasDominicanasDia, ...loteriasAmericanasDia];
            } else { // noche
                loterias = [...loteriasDominicanasNoche, ...loteriasAmericanasNoche];
            }
        }
        // Caso 3: Ambos filtros específicos
        else {
            if (filtroPais === 'dominicanas') {
                loterias = filtroHorario === 'dia' ? [...loteriasDominicanasDia] : [...loteriasDominicanasNoche];
            } else { // americanas
                loterias = filtroHorario === 'dia' ? [...loteriasAmericanasDia] : [...loteriasAmericanasNoche];
            }
        }
        
        return loterias;
    }
    
    // ELIMINADO: actualizarEtiquetasFiltros ya no es necesario con los nuevos botones

    // --- LÓGICA DE CARGA Y MANEJO DE DATOS ---
    async function cargarDatosConteo() {
        loadingDiv.style.display = 'block';
        errorDiv.style.display = 'none';
        
        // Si el filtro es tipo 'range', usar el backend PHP
if (currentFilter.type === 'range') {
    try {
        const tipoConteo = toggleSwitch.checked ? 'ConteoAll' : 'ConteoEnPrimera';
        
        // CRÍTICO: Obtener las loterías filtradas según país/horario
        const loteriasFiltradas = obtenerLoteriasFiltradas();
        let aliasesParaBackend = [];
        
        if (selectedAliases.includes('Todas')) {
            // Si "Todas" está seleccionado, enviar solo las loterías filtradas
            aliasesParaBackend = loteriasFiltradas;
        } else {
            // Si hay selección manual, aplicar intersección con filtros
            aliasesParaBackend = selectedAliases.filter(alias => loteriasFiltradas.includes(alias));
        }
        
        const aliasesJSON = JSON.stringify(aliasesParaBackend);
        
        const params = new URLSearchParams({
            startDate: currentFilter.startDate,
            endDate: currentFilter.endDate,
            tipoConteo: tipoConteo,
            aliases: aliasesJSON
        });
                
                const response = await fetch(`procesar_conteo.php?${params.toString()}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const resultado = await response.json();
                
                if (resultado.error) {
                    throw new Error(resultado.mensaje);
                }
                
                conteoData = {
                    '__PHP_BACKEND_RESULT__': {
                        'estadisticas': {
                            'ConteoEnPrimera': { 'general': {} },
                            'ConteoAll': { 'general': {} }
                        }
                    }
                };
                
                conteoData['__PHP_BACKEND_RESULT__'].estadisticas[tipoConteo].general = resultado.conteos;
                
                loadingDiv.style.display = 'none';
                console.log('Estadísticas del backend:', resultado.estadisticas);
                
                return;
                
            } catch (error) {
                loadingDiv.style.display = 'none';
                errorDiv.textContent = `❌ Error al procesar el rango de fechas: ${error.message}`;
                errorDiv.style.display = 'block';
                throw error;
            }
        }
        
        // Lógica existente para filtros tipo 'month' y 'year'
        let filePath = '';
        
        if (currentFilter.type === 'month' && currentFilter.year && currentFilter.month) {
            const yearPart = currentFilter.year.replace('Año ', '');
            filePath = `json/conteo-${yearPart}/conteo-${currentFilter.month}/conteo-${currentFilter.month}.json`;
        } else if (currentFilter.type === 'year' && currentFilter.year) {
            const yearPart = currentFilter.year.replace('Año ', '');
            const months = monthsByYear[currentFilter.year] ? [...monthsByYear[currentFilter.year]] : [];
            const allYearData = {};
            const fetchPromises = months.map(async month => {
                const url = `json/conteo-${yearPart}/conteo-${month}/conteo-${month}.json`;
                try {
                    const response = await fetch(url + `?v=${new Date().getTime()}`);
                    if (!response.ok) return null;
                    const data = await response.json();
                    return data;
                } catch (err) {
                    console.error(`Error al cargar ${url}:`, err);
                    return null;
                }
            });
            const results = await Promise.all(fetchPromises);
            results.forEach(monthData => {
                if (monthData) {
                    for (const alias in monthData) {
                        if (!allYearData[alias]) {
                            allYearData[alias] = { 'estadisticas': { 'ConteoEnPrimera': { 'general': {} }, 'ConteoAll': { 'general': {} } } };
                        }
                        agregarConteos(allYearData[alias].estadisticas.ConteoEnPrimera.general, monthData[alias].estadisticas.ConteoEnPrimera.general);
                        agregarConteos(allYearData[alias].estadisticas.ConteoAll.general, monthData[alias].estadisticas.ConteoAll.general);
                    }
                }
            });
            conteoData = allYearData;
            loadingDiv.style.display = 'none';
            return;
        }

        try {
            const response = await fetch(filePath + `?v=${new Date().getTime()}`);
            if (!response.ok) throw new Error(`HTTP ${response.status} al cargar ${filePath}`);
            conteoData = await response.json();
            loadingDiv.style.display = 'none';
        } catch (error) {
            loadingDiv.style.display = 'none';
            errorDiv.textContent = `❌ Error fatal al cargar los datos: ${error.message}. Verifique que el archivo exista en el directorio y que el nombre sea correcto.`;
            errorDiv.style.display = 'block';
            throw error;
        }
    }

    async function cargarAlias() {
        try {
            const response = await fetch(`alias.json?v=${new Date().getTime()}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            aliasData = await response.json();
        } catch (error) {
            console.error('Error al cargar alias:', error);
        }
    }
    
    async function cargarDisponibilidadDeArchivos() {
        try {
            const response = await fetch('get_available_data.php');
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            monthsByYear = data;
        } catch (error) {
            console.error('Error al cargar la disponibilidad de archivos:', error);
            monthsByYear = {};
        }
    }
    
    function calcularConteoAgregado() {
    if (!conteoData || !aliasData || selectedAliases.length === 0) return null;

    const tipoConteo = toggleSwitch.checked ? 'ConteoAll' : 'ConteoEnPrimera';
    
    // Si los datos vienen del backend PHP, retornarlos directamente
    if (conteoData['__PHP_BACKEND_RESULT__']) {
        return conteoData['__PHP_BACKEND_RESULT__'].estadisticas[tipoConteo].general;
    }
    
    // Obtener loterías según filtros de país y horario
    const loteriasFiltradas = obtenerLoteriasFiltradas();
    
    const conteoFinal = {};
    let aliasesAProcesar = [];
    
    // Si "Todas" está seleccionado en el selector de loterías
    if (selectedAliases.includes('Todas')) {
        // Usar SOLO las loterías filtradas por país/horario
        aliasesAProcesar = loteriasFiltradas;
    } else {
        // Si hay selección manual, aplicar intersección con filtros
        aliasesAProcesar = selectedAliases.filter(alias => loteriasFiltradas.includes(alias));
    }

    aliasesAProcesar.forEach(alias => {
        const dataAlias = conteoData[alias];
        if (!dataAlias?.estadisticas?.[tipoConteo]) return;
        
        agregarConteos(conteoFinal, dataAlias.estadisticas[tipoConteo].general);
    });
    return conteoFinal;
}

    // --- LÓGICA DE ACTUALIZACIÓN DE UI ---
    async function actualizarTabla() {
        limpiarCeldasTabla();
        loadingDiv.style.display = 'block';

        try {
            if (currentFilter.type === 'range' || currentFilter.type === 'year') {
                await cargarDatosConteo();
            } else {
                let year = new Date().getFullYear();
                let month = MESES[new Date().getMonth()];
                
                if(currentFilter.year) year = currentFilter.year.replace('Año ', '');
                if(currentFilter.month) month = currentFilter.month;
                
                const url = `json/conteo-${year}/conteo-${month}/conteo-${month}.json`;
                const response = await fetch(url + `?v=${new Date().getTime()}`);
                if (!response.ok) throw new Error(`HTTP ${response.status} al cargar ${url}`);
                conteoData = await response.json();
            }
            
            const datosConteo = calcularConteoAgregado();
            
            if (datosConteo) {
                for (const numeroStr in datosConteo) {
                    const celda = document.getElementById(`num-${numeroStr}`);
                    if (celda) {
                        const conteo = datosConteo[numeroStr];
                        if (conteo > 0) {
                            celda.textContent = conteo;
                            celda.classList.remove('empty-cell');
                        }
                    }
                }
            }
            
            loadingDiv.style.display = 'none';
            actualizarEtiquetasSwitch();
            actualizarEstadoFiltro();
            
        } catch (error) {
            loadingDiv.style.display = 'none';
            errorDiv.textContent = `❌ Error al cargar los datos para el período seleccionado: ${error.message}`;
            errorDiv.style.display = 'block';
            console.error('Error en actualizarTabla:', error);
        }
    }

    function actualizarEtiquetasSwitch() {
        document.getElementById('label1').classList.toggle('active', !toggleSwitch.checked);
        document.getElementById('label2').classList.toggle('active', toggleSwitch.checked);
    }
    
    function cerrarDropdownsAtajos() {
        document.querySelectorAll('.shortcut-dropdown').forEach(dropdown => {
            dropdown.remove();
        });
    }
    
    function actualizarEstadoFiltro() {
        filterStatusContent.innerHTML = '';
        cerrarDropdownsAtajos();
        
        const fragment = document.createDocumentFragment();

        if (currentFilter.type === 'range') {
            // NUEVO: Verificar si es una fecha única (inicio = fin)
            if (currentFilter.startDate === currentFilter.endDate) {
                const text1 = document.createElement('span');
                text1.textContent = 'Mostrando el conteo del día: ';
                fragment.appendChild(text1);

                const date1 = document.createElement('span');
                date1.textContent = formatDateLong(currentFilter.startDate);
                date1.style.fontWeight = 'bold';
                fragment.appendChild(date1);
            } else {
                // Es un rango de fechas normal
                const text1 = document.createElement('span');
                text1.textContent = 'Mostrando el conteo desde ';
                fragment.appendChild(text1);

                const date1 = document.createElement('span');
                date1.textContent = formatDateLong(currentFilter.startDate);
                date1.style.fontWeight = 'bold';
                fragment.appendChild(date1);

                const text2 = document.createElement('span');
                text2.textContent = ' hasta ';
                fragment.appendChild(text2);
                
                const date2 = document.createElement('span');
                date2.textContent = formatDateLong(currentFilter.endDate);
                date2.style.fontWeight = 'bold';
                fragment.appendChild(date2);
            }
        } else if (currentFilter.type === 'month' || currentFilter.type === 'year') {
            const text = document.createElement('span');
            text.textContent = 'Mostrando el conteo de: ';
            fragment.appendChild(text);

            if (currentFilter.month) {
                const monthShortcut = document.createElement('span');
                monthShortcut.className = 'filter-shortcut';
                monthShortcut.textContent = currentFilter.month.charAt(0).toUpperCase() + currentFilter.month.slice(1);
                monthShortcut.dataset.type = 'month';
                fragment.appendChild(monthShortcut);
            }

            const yearShortcut = document.createElement('span');
            yearShortcut.className = 'filter-shortcut';
            yearShortcut.textContent = currentFilter.year.replace('Año ', '');
            yearShortcut.dataset.type = 'year';
            fragment.appendChild(yearShortcut);

        } else {
            const text = document.createElement('span');
            text.textContent = 'Mostrando conteo general.';
            fragment.appendChild(text);
        }
        
        filterStatusContent.appendChild(fragment);
    }

    function popularFiltrosFecha() {
        if (!monthsByYear) return;
        const years = Object.keys(monthsByYear);

        yearFilter.innerHTML = '<option value="">Seleccionar Año</option>';
        [...years].sort((a,b) => b.localeCompare(a, undefined, {numeric: true})).forEach(year => yearFilter.add(new Option(year.replace('Año ', ''), year)));
    }

    function popularMesesParaAño(selectedYear) {
        monthFilter.innerHTML = '<option value="">Seleccionar Mes</option>';
        monthFilter.disabled = true;
        
        if (selectedYear && monthsByYear[selectedYear]) {
            const sortedMonths = [...monthsByYear[selectedYear]].sort((a, b) => MESES.indexOf(a) - MESES.indexOf(b));
            sortedMonths.forEach(month => monthFilter.add(new Option(month.charAt(0).toUpperCase() + month.slice(1), month)));
            monthFilter.disabled = false;
        }
    }
    
    function updateFilterUI() {
        if (!monthsByYear) return;
        isUpdatingFilters = true;
        popularFiltrosFecha();
        
        singleDateInput.value = ''; // NUEVO: Limpiar campo de fecha única
        yearFilter.value = '';
        monthFilter.innerHTML = '<option value="">Seleccionar Mes</option>';
        monthFilter.disabled = true;
        startDateInput.value = '';
        endDateInput.value = '';
        
        if (currentFilter.type === 'range') {
            startDateInput.value = currentFilter.startDate;
            endDateInput.value = currentFilter.endDate;
            // Si es fecha única, también llenar el campo singleDate
            if (currentFilter.startDate === currentFilter.endDate) {
                singleDateInput.value = currentFilter.startDate;
            }
        } else if (currentFilter.type === 'year' || currentFilter.type === 'month') {
            if (currentFilter.year) {
                yearFilter.value = currentFilter.year;
                popularMesesParaAño(currentFilter.year);
                if (currentFilter.type === 'month' && currentFilter.month) {
                    monthFilter.value = currentFilter.month;
                }
            }
        }
        isUpdatingFilters = false;
        actualizarEstadoFiltro();
    }

    // --- LÓGICA DE MANEJO DE EVENTOS ---
    
    // NUEVA FUNCIÓN: Manejar clic en fecha única
    function handleSingleDateClick(selectedDate) {
        if (!selectedDate) {
            return;
        }
        
        // Limpiar los otros filtros
        isUpdatingFilters = true;
        yearFilter.value = '';
        monthFilter.innerHTML = '<option value="">Seleccionar Mes</option>';
        monthFilter.disabled = true;
        startDateInput.value = '';
        endDateInput.value = '';
        isUpdatingFilters = false;
        
        // Establecer el filtro como rango con la misma fecha de inicio y fin
        currentFilter = { 
            type: 'range', 
            startDate: selectedDate, 
            endDate: selectedDate, 
            year: null, 
            month: null 
        };
        
        saveAndApplyFilter();
    }
    
    function configurarEventos() {
        navToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
            navToggle.setAttribute('aria-expanded', !isExpanded);
            navMenu.classList.toggle('active');
        });
        
        navMenu.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
            navMenu.classList.remove('active');
            navToggle.setAttribute('aria-expanded', 'false');
        }));

        toggleSwitch.addEventListener('change', () => {
            localStorage.setItem(STORAGE_KEY_SWITCH, toggleSwitch.checked);
            actualizarTabla();
        });

        aliasDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation(); 
            aliasDropdownContent.classList.toggle('show');
            aliasDropdownBtn.classList.toggle('active');
        });
        
        document.addEventListener('click', (e) => {
            if (!aliasDropdownBtn.contains(e.target) && !aliasDropdownContent.contains(e.target)) {
                aliasDropdownContent.classList.remove('show');
                aliasDropdownBtn.classList.remove('active');
            }
            if (!navMenu.contains(e.target) && !navToggle.contains(e.target)) {
                navMenu.classList.remove('active');
                navToggle.setAttribute('aria-expanded', 'false');
            }
            if (!e.target.closest('.filter-shortcut') && !e.target.closest('.shortcut-dropdown')) {
                cerrarDropdownsAtajos();
            }
        });
        
        clearAliasBtn.addEventListener('click', () => {
            selectedAliases = ['Todas'];
            localStorage.setItem(STORAGE_KEY_ALIASES, JSON.stringify(selectedAliases));
            actualizarUISelectorAlias();
            actualizarTabla();
        });
        
        applyAliasBtn.addEventListener('click', () => {
            const checkedAliases = [...aliasOptions.querySelectorAll('input:checked')].map(cb => cb.value);
            selectedAliases = checkedAliases.length > 0 ? checkedAliases : ['Todas'];
            localStorage.setItem(STORAGE_KEY_ALIASES, JSON.stringify(selectedAliases));
            actualizarUISelectorAlias();
            actualizarTabla();
            aliasDropdownContent.classList.remove('show');
            aliasDropdownBtn.classList.remove('active');
        });
        
        yearFilter.addEventListener('change', handleYearMonthFilterChange);
        monthFilter.addEventListener('change', handleYearMonthFilterChange);
        startDateInput.addEventListener('change', handleDateRangeChange);
        endDateInput.addEventListener('change', handleDateRangeChange);

        // NUEVO: Event listeners para fecha única
        
        // Botón "Día Específico" - abre el selector de fecha
        if (specificDayBtn) {
            specificDayBtn.addEventListener('click', () => {
                singleDateInput.style.display = 'block';
                singleDateInput.showPicker(); // Abre el calendario nativo
                singleDateInput.focus();
            });
        }
        
        // Cuando se selecciona una fecha en el calendario
        singleDateInput.addEventListener('change', () => {
            const selectedDate = singleDateInput.value;
            if (selectedDate) {
                handleSingleDateClick(selectedDate);
                singleDateInput.style.display = 'none'; // Ocultar después de seleccionar
            }
        });
        
        // Botón "Hoy" - carga directamente la fecha de hoy
        if (todayBtn) {
    todayBtn.addEventListener('click', () => {
        const today = new Date().toLocaleDateString("sv-SE", { timeZone: "America/Santo_Domingo" });
        handleSingleDateClick(today);
    });
}
        
        // NUEVO: Event listeners para switches de 3 posiciones
        if (paisSwitch) {
            paisSwitch.addEventListener('click', (e) => {
                const button = e.target.closest('.switch-option');
                if (!button) return;
                
                // Desactivar todos los botones del switch
                paisSwitch.querySelectorAll('.switch-option').forEach(btn => btn.classList.remove('active'));
                // Activar el botón clickeado
                button.classList.add('active');
                
                // Actualizar estado
                filtroPais = button.dataset.value;
                localStorage.setItem(STORAGE_KEY_PAIS, filtroPais);
                actualizarTabla();
            });
        }
        
        if (horarioSwitch) {
            horarioSwitch.addEventListener('click', (e) => {
                const button = e.target.closest('.switch-option');
                if (!button) return;
                
                // Desactivar todos los botones del switch
                horarioSwitch.querySelectorAll('.switch-option').forEach(btn => btn.classList.remove('active'));
                // Activar el botón clickeado
                button.classList.add('active');
                
                // Actualizar estado
                filtroHorario = button.dataset.value;
                localStorage.setItem(STORAGE_KEY_HORARIO, filtroHorario);
                actualizarTabla();
            });
        }

        clearDateFilterBtn.addEventListener('click', () => {
            localStorage.removeItem(STORAGE_KEY_FILTERS);
            setDefaultFilter();
            updateFilterUI();
            saveAndApplyFilter();
        });

        filterStatusDiv.addEventListener('click', handleShortcutClick);
    }
    
    function handleYearMonthFilterChange(event) {
        if (isUpdatingFilters) return;
        
        const year = yearFilter.value;
        const month = monthFilter.value;
        
        if (event && event.target === yearFilter) popularMesesParaAño(year);
        
        isUpdatingFilters = true;
        singleDateInput.value = ''; // NUEVO: Limpiar campo de fecha única
        startDateInput.value = '';
        endDateInput.value = '';
        isUpdatingFilters = false;
        
        if (year && month) {
            currentFilter = { type: 'month', year, month, startDate: '', endDate: '' };
        } else if (year) {
            currentFilter = { type: 'year', year, month: null, startDate: '', endDate: '' };
        } else {
            setDefaultFilter();
        }
        saveAndApplyFilter();
        updateFilterUI();
    }
    
    function handleDateRangeChange(event) {
        if (isUpdatingFilters) return;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        if (startDate && endDate) {
            if (new Date(startDate) > new Date(endDate)) {
                alert('La fecha de inicio no puede ser posterior a la fecha de fin.');
                event.target.value = '';
                return;
            }
            isUpdatingFilters = true;
            singleDateInput.value = ''; // NUEVO: Limpiar campo de fecha única
            yearFilter.value = '';
            monthFilter.innerHTML = '<option value="">Seleccionar Mes</option>';
            monthFilter.disabled = true;
            isUpdatingFilters = false;
            
            currentFilter = { type: 'range', startDate, endDate, year: null, month: null };
            saveAndApplyFilter();
        }
    }

    function handleShortcutClick(event) {
        const target = event.target;
        if (!target.classList.contains('filter-shortcut')) return;

        event.stopPropagation();
        cerrarDropdownsAtajos();

        const type = target.dataset.type;
        const dropdown = document.createElement('div');
        dropdown.className = 'shortcut-dropdown show';

        if (type === 'year') {
            const years = Object.keys(monthsByYear).sort((a,b) => b.localeCompare(a, undefined, {numeric: true}));
            years.forEach(yearKey => {
                const option = document.createElement('div');
                option.className = 'shortcut-option';
                option.textContent = yearKey.replace('Año ', '');
                option.dataset.value = yearKey;
                if (yearKey === currentFilter.year) option.classList.add('selected');
                
                option.addEventListener('click', () => {
                    const newYearKey = yearKey;
                    const currentMonth = currentFilter.month;

                    if (currentMonth && monthsByYear[newYearKey] && monthsByYear[newYearKey].includes(currentMonth)) {
                        currentFilter = { type: 'month', year: newYearKey, month: currentMonth, startDate: '', endDate: '' };
                    } else {
                        currentFilter = { type: 'year', year: newYearKey, month: null, startDate: '', endDate: '' };
                    }
                    
                    saveAndApplyFilter();
                    updateFilterUI();
                });
                dropdown.appendChild(option);
            });
        } else if (type === 'month') {
            const availableMonths = [...monthsByYear[currentFilter.year]].sort((a, b) => MESES.indexOf(a) - MESES.indexOf(b));
            availableMonths.forEach(monthKey => {
                const option = document.createElement('div');
                option.className = 'shortcut-option';
                option.textContent = monthKey.charAt(0).toUpperCase() + monthKey.slice(1);
                option.dataset.value = monthKey;
                if (monthKey === currentFilter.month) option.classList.add('selected');

                option.addEventListener('click', () => {
                    currentFilter = { type: 'month', year: currentFilter.year, month: monthKey, startDate: '', endDate: '' };
                    saveAndApplyFilter();
                    updateFilterUI();
                });
                dropdown.appendChild(option);
            });
        }

        target.parentNode.appendChild(dropdown);
    }
    
    function saveAndApplyFilter() {
        localStorage.setItem(STORAGE_KEY_FILTERS, JSON.stringify(currentFilter));
        actualizarTabla();
    }

    function crearOpcionesAlias() {
        if (!aliasData) return;
        aliasOptions.innerHTML = '';
        
        const createOption = (value, text) => {
            const div = document.createElement('div');
            div.className = 'alias-option';
            const checkbox = Object.assign(document.createElement('input'), { type: 'checkbox', id: `alias-${value}`, value, checked: selectedAliases.includes(value) });
            const label = Object.assign(document.createElement('label'), { htmlFor: `alias-${value}`, textContent: text });
            div.append(checkbox, label);
            return div;
        };

        aliasOptions.append(createOption('Todas', 'Todas las Loterías'));
        const separator = Object.assign(document.createElement('hr'), { style: 'margin: 8px 0; border: none; border-top: 1px solid #ddd;' });
        aliasOptions.append(separator);
        
        Object.values(aliasData).sort().forEach(alias => aliasOptions.append(createOption(alias, alias)));

        aliasOptions.addEventListener('change', (e) => {
            if (e.target.type !== 'checkbox') return;
            const todasCheckbox = document.getElementById('alias-Todas');
            if (e.target.value === 'Todas' && e.target.checked) {
                aliasOptions.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = (cb.value === 'Todas'));
            } else if (e.target.value !== 'Todas' && e.target.checked) {
                todasCheckbox.checked = false;
            }
        });
    }

    function actualizarUISelectorAlias() {
        if (selectedAliases.includes('Todas') || selectedAliases.length === 0) {
            aliasSelectedText.textContent = 'Todas las loterías';
        } else if (selectedAliases.length === 1) {
            aliasSelectedText.textContent = selectedAliases[0];
        } else {
            aliasSelectedText.textContent = `${selectedAliases.length} Loterías`;
        }
        crearOpcionesAlias();
    }
    
    function setDefaultFilter() {
        const now = new Date();
        const currentYearKey = `Año ${now.getFullYear()}`;
        const currentMonthKey = MESES[now.getMonth()];
        currentFilter = { type: 'month', year: currentYearKey, month: currentMonthKey, startDate: '', endDate: '' };
    }

    // --- FUNCIÓN DE INICIALIZACIÓN ---
    (async function inicializar() {
        //document.getElementById('current-year').textContent = new Date().getFullYear();
        crearTabla();
        
        toggleSwitch.checked = JSON.parse(localStorage.getItem(STORAGE_KEY_SWITCH)) || false;
        selectedAliases = JSON.parse(localStorage.getItem(STORAGE_KEY_ALIASES)) || ['Todas'];
        
        // NUEVO: Cargar filtros de país y horario desde localStorage
        filtroPais = localStorage.getItem(STORAGE_KEY_PAIS) || 'todas';
        filtroHorario = localStorage.getItem(STORAGE_KEY_HORARIO) || 'todas';
        
        // Sincronizar botones de 3 posiciones con los valores cargados
        if (paisSwitch) {
            paisSwitch.querySelectorAll('.switch-option').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.value === filtroPais);
            });
        }
        
        if (horarioSwitch) {
            horarioSwitch.querySelectorAll('.switch-option').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.value === filtroHorario);
            });
        }
        
        const savedFilters = JSON.parse(localStorage.getItem(STORAGE_KEY_FILTERS));
        if (savedFilters && savedFilters.type) {
            currentFilter = savedFilters;
        } else {
            setDefaultFilter();
        }

        try {
            await cargarAlias();
            await cargarDisponibilidadDeArchivos();
            
            loadingDiv.style.display = 'none';
            
            configurarEventos();
            actualizarUISelectorAlias();
            updateFilterUI();
            saveAndApplyFilter();

        } catch (e) {
            console.error("Inicialización fallida:", e);
        }
    })();
});
// ============================================
// ANÁLISIS CON IA - GEMINI
// ============================================

// Función principal de análisis
// Función principal de análisis
async function analizarNumerosCalientes() {
    const btn = document.getElementById('numerosCalientesBtn');
    const resultadoDiv = document.getElementById('iaResultado');
    
    if (!btn || !resultadoDiv) {
        console.error('No se encontraron elementos del botón IA');
        return;
    }
    
    // Obtener elementos directamente del DOM (no usar variables del scope principal)
    const yearFilterElement = document.getElementById('yearFilter');
    const toggleSwitchElement = document.getElementById('dataToggle');
    const paisSwitchElement = document.querySelector('#paisSwitch .switch-option.active');
    const horarioSwitchElement = document.querySelector('#horarioSwitch .switch-option.active');
    
    const yearValue = yearFilterElement?.value || new Date().getFullYear().toString();
    const modeValue = toggleSwitchElement?.checked ? '123' : '1';
    const countryValue = paisSwitchElement?.dataset.value || 'dominicanas';
    const timeValue = horarioSwitchElement?.dataset.value || 'dia';
    
    const filtros = {
        year: yearValue,
        country: countryValue,
        time: timeValue,
        countMode: modeValue,
        analysis: 'numeros_calientes'
    };
    
    console.log('Filtros enviados:', filtros);
    
    // Mostrar loading
    btn.disabled = true;
    btn.textContent = '⏳ Analizando...';
    resultadoDiv.style.display = 'block';
    resultadoDiv.innerHTML = '<div class="loading">Procesando datos con IA...</div>';
    
    try {
        const response = await fetch('gemini_analysis.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(filtros)
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Respuesta de la API:', data);
        
        if (data.success) {
            mostrarResultadoIA(data.data, resultadoDiv);
        } else {
            resultadoDiv.innerHTML = `<div class="error">Error: ${data.error}</div>`;
        }
        
    } catch (error) {
        console.error('Error completo:', error);
        resultadoDiv.innerHTML = `<div class="error">Error de conexión: ${error.message}</div>`;
    } finally {
        btn.disabled = false;
        btn.textContent = '🔥 Números Calientes';
    }
}

// Mostrar resultado formateado
function mostrarResultadoIA(data, container) {
    if (!data || !data.top_numeros) {
        container.innerHTML = '<div class="error">No se recibieron datos válidos</div>';
        return;
    }
    
    let html = `
        <div style="background: rgba(255,255,255,0.1); padding: 1rem; border-radius: 8px; color: #fff;">
            <h5 style="margin: 0 0 1rem 0;">📊 Análisis: ${data.periodo || 'Sin período'}</h5>
            <p style="font-size: 13px; margin-bottom: 1rem;">Total de sorteos: ${data.total_sorteos_analizados || 0}</p>
            
            <div style="max-height: 300px; overflow-y: auto;">
                <table style="width: 100%; font-size: 12px;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.3);">
                            <th style="padding: 0.5rem;">Número</th>
                            <th style="padding: 0.5rem;">Veces</th>
                            <th style="padding: 0.5rem;">%</th>
                            <th style="padding: 0.5rem;">Mejor en</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    data.top_numeros.forEach(num => {
        html += `
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                <td style="padding: 0.5rem; font-weight: bold; font-size: 16px;">${num.numero}</td>
                <td style="padding: 0.5rem;">${num.frecuencia_absoluta}</td>
                <td style="padding: 0.5rem;">${num.frecuencia_relativa}</td>
                <td style="padding: 0.5rem; font-size: 11px;">${num.loteria_mayor_probabilidad}</td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
            
            <p style="font-size: 12px; margin-top: 1rem; font-style: italic; opacity: 0.8;">
                ${data.observaciones || ''}
            </p>
        </div>
    `;
    
    container.innerHTML = html;
}

// Inicializar botón cuando carga la página (FUERA del DOMContentLoaded principal)
window.addEventListener('load', function() {
    console.log('Iniciando conexión del botón IA...');
    
    const btnIA = document.getElementById('numerosCalientesBtn');
    if (btnIA) {
        btnIA.addEventListener('click', analizarNumerosCalientes);
        console.log('✅ Botón IA conectado correctamente');
    } else {
        console.error('❌ No se encontró el botón numerosCalientesBtn');
    }
    
    // ============================================
    // MODAL DE INFORMACIÓN
    // ============================================
    const btnInfo = document.getElementById('btnInfo');
    const infoModal = document.getElementById('estadisticas');
    const infoModalOverlay = document.getElementById('infoModalOverlay');
    const closeInfoModal = document.getElementById('closeInfoModal');
    
    // Función para abrir el modal
    function openInfoModal() {
        if (infoModal && infoModalOverlay) {
            infoModal.classList.add('show');
            infoModalOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }
    
    // Función para cerrar el modal
    function closeModalInfo() {
        if (infoModal && infoModalOverlay) {
            infoModal.classList.remove('show');
            infoModalOverlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    // Event listeners para abrir modal
    if (btnInfo) {
        btnInfo.addEventListener('click', openInfoModal);
    }
    
    // Event listeners para cerrar modal
    if (closeInfoModal) {
        closeInfoModal.addEventListener('click', closeModalInfo);
    }
    
    if (infoModalOverlay) {
        infoModalOverlay.addEventListener('click', closeModalInfo);
    }
    
    // Cerrar modal con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModalInfo();
        }
    });
});