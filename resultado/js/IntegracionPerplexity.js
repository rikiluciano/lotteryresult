/**
 * SISTEMA V4.0 - CHAT INTERACTIVO + PRIMERA POSICIÓN
 * Integración con Perplexity AI
 */

const PERPLEXITY_API_KEY = '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';
const PHP_ENDPOINT = 'motor_estadistico_v5.php';

let conversacionActual = null;

/**
 * Detectar tipo de consulta del usuario
 */
function detectarTipoConsulta(texto) {
    texto = texto.toLowerCase();
    
    // Patrones para "números para hoy"
    const patronesHoy = [
        'qué números jugar hoy',
        'cuáles jugar hoy',
        'números para hoy',
        'recomienda para hoy',
        'qué pega hoy',
        'qué sale hoy',
        'números calientes',
        'mejores números'
    ];
    
    for (let patron of patronesHoy) {
        if (texto.includes(patron)) {
            return { tipo: 'numeros_para_hoy', numero: null };
        }
    }
    
    // Buscar si menciona un número específico
    const match = texto.match(/\b(\d{1,2})\b/);
    if (match) {
        const numero = parseInt(match[1]);
        if (numero >= 0 && numero <= 99) {
            return { tipo: 'numero_especifico', numero: numero };
        }
    }
    
    return { tipo: 'desconocido', numero: null };
}

/**
 * Analizar con chat
 */
async function analizarChat() {
    const input = document.getElementById('chat-input');
    const texto = input.value.trim();
    
    if (!texto) return;
    
    try {
        // Validar API key
        if (!PERPLEXITY_API_KEY || PERPLEXITY_API_KEY === 'TU_API_KEY_AQUI') {
            throw new Error('Configura tu API Key de Perplexity en integracion_perplexity_v4.js línea 8');
        }
        
        // Agregar mensaje del usuario al chat
        agregarMensaje(texto, 'usuario');
        input.value = '';
        
        // Detectar tipo de consulta
        const consulta = detectarTipoConsulta(texto);
        
        if (consulta.tipo === 'desconocido') {
            agregarMensaje('No entendí tu consulta. Pregunta algo como: "¿Qué números jugar hoy?" o "Analiza el número 03"', 'error');
            return;
        }
        
        // Mostrar loading
        agregarMensaje('Analizando...', 'loading');
        
        // Llamar al PHP según el tipo
        const params = consulta.tipo === 'numeros_para_hoy' 
            ? `modo=numeros_para_hoy`
            : `numero=${consulta.numero}&modo=numero_especifico`;
        
        const response = await fetch(`${PHP_ENDPOINT}?${params}`);
        
        if (!response.ok) {
            throw new Error(`Error del servidor: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Error en el análisis');
        }
        
        // Quitar loading
        document.querySelector('.mensaje.loading')?.remove();
        
        // Crear prompt según el modo
        const prompt = consulta.tipo === 'numeros_para_hoy'
            ? crearPromptNumerosParaHoy(data.contexto_ia)
            : crearPromptNumeroEspecifico(data.contexto_ia);
        
        // Enviar a Perplexity
        const respuestaIA = await enviarAPerplexity(prompt);
        
        // Mostrar respuesta
        agregarMensaje(respuestaIA, 'ia');
        
        // Guardar conversación
        conversacionActual = data;
        
    } catch (error) {
        document.querySelector('.mensaje.loading')?.remove();
        console.error('Error:', error);
        agregarMensaje(`Error: ${error.message}`, 'error');
    }
}

/**
 * PROMPT PARA NÚMERO ESPECÍFICO
 */
function crearPromptNumeroEspecifico(contexto) {
    const otrasPos = contexto.estado_actual.otras_posiciones;
    const otrasPosTexto = otrasPos ? 
        `(Aunque salió hace ${otrasPos.hace_dias} día(s) en ${otrasPos.posicion}ª posición en ${otrasPos.loteria}, eso no cuenta pa las quinielas)` : 
        '';
    
    return `Eres un analista de loterías de República Dominicana. Habla natural, como un dominicano.

CONTEXTO CULTURAL:
- Las quinielas son apostar a UN número en primera posición
- La gente solo le da importancia a PRIMERA POSICIÓN
- Si no salió en primera, es como si no salió
- Lo que paga es: Quinielas 60-70×1, Palés 1200-1500×1, Tripletas 20,000×1

DATOS DEL NÚMERO ${contexto.numero}:

🎯 ESTADO ACTUAL (PRIMERA POSICIÓN):
${contexto.estado_actual.descripcion}

- Última vez en PRIMERA: ${contexto.estado_actual.ultima_en_primera.fecha}
- Hace ${contexto.estado_actual.dias_sin_primera} día(s)
- Salió en: ${contexto.estado_actual.ultima_en_primera.loteria}
- Con: ${contexto.estado_actual.ultima_en_primera.con_numeros}

${otrasPos ? `📝 OTRAS POSICIONES: ${otrasPosTexto}` : ''}

📊 EVALUACIÓN:
- ¿Es buen número HOY? ${contexto.evaluacion.es_buen_numero_hoy ? 'SÍ' : 'NO'}
- Razón: ${contexto.evaluacion.razon}
- Ciclo promedio: ${contexto.evaluacion.ciclo_promedio}

🎰 LOTERÍAS:
- Mejor lotería en primera: ${contexto.loterias.mejor_primera}
- Total veces en primera: ${contexto.historico.total_en_primera}

---

TAREA:
Responde de forma natural y conversacional sobre el número ${contexto.numero}.

IMPORTANTE:
- Usa lenguaje dominicano natural (no técnico)
- Di "EN la Leidsa", "EN la Nacional" (no "con")
- NO menciones cálculos (60×1, etc.) - la gente ya lo sabe
- Si no es buen número, di "espera 2-3 días" (no "su ciclo")
- ENFÓCATE en primera posición
- Si salió en otras posiciones, menciónalo PERO deja claro que no cuenta

Estructura tu respuesta:
1. Estado del número (cuántos días sin salir EN PRIMERA)
2. Si salió en otras posiciones recientemente (aclarar que no cuenta)
3. Recomendación clara: ¿Jugar hoy o esperar?
4. Si es bueno: en qué lotería jugarlo

Habla directo, sin rodeos.`;
}

/**
 * PROMPT PARA NÚMEROS PARA HOY
 */
function crearPromptNumerosParaHoy(contexto) {
    const numeros = contexto.numeros_calientes.map(n => 
        `- ${n.numero} en ${n.loteria} (${n.dias_sin_salir_primera} días sin salir en primera)`
    ).join('\n');
    
    const superPale = contexto.super_pale ? 
        `\n🎯 SÚPER PALÉ:\n${contexto.super_pale.numero_1} en ${contexto.super_pale.loteria_1} + ${contexto.super_pale.numero_2} en ${contexto.super_pale.loteria_2}` :
        '';
    
    return `Eres un analista de loterías de República Dominicana. Habla natural, como un dominicano.

CONTEXTO:
La persona preguntó: "¿Qué números jugar HOY?"

Se hizo una búsqueda retroactiva de 3 días para encontrar números que:
1. Hace 3 días tenían alta probabilidad de salir en primera
2. AÚN NO han salido en primera posición
3. Por eso están "calientes" HOY

NÚMEROS CALIENTES PARA HOY:
${numeros}
${superPale}

CONTEXTO CULTURAL:
- Quinielas = apostar a primera posición
- Súper palé = dos números en primera, loterias diferentes, paga bien

---

TAREA:
Recomienda los mejores números para jugar HOY, hablando natural.

IMPORTANTE:
- Usa lenguaje dominicano (no técnico)
- Di "EN la Leidsa", "EN la Nacional"
- NO menciones "3000×1" ni cálculos
- Sé específico: qué número EN qué lotería
- Si hay súper palé, sugiérelo pero SIN mencionar cuánto paga

Estructura:
1. "Mira, te voy a dar los números calientes HOY:"
2. Lista 2-3 números con su lotería
3. Si hay súper palé, sugiérelo naturalmente
4. "Dale al palo HOY" o "Juega hoy y mañana máximo"

Habla como un pana dando un consejo.`;
}

/**
 * Enviar a Perplexity
 */
async function enviarAPerplexity(prompt) {
    try {
        const response = await fetch('https://api.perplexity.ai/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${PERPLEXITY_API_KEY}`
            },
            body: JSON.stringify({
                model: 'llama-3.1-sonar-large-128k-online',
                messages: [
                    {
                        role: 'system',
                        content: 'Eres un analista experto de loterías dominicanas. Hablas de forma natural y directa, como un dominicano conversando con otro. Nunca inventas datos.'
                    },
                    {
                        role: 'user',
                        content: prompt
                    }
                ],
                temperature: 0.4,
                max_tokens: 1500,
                stream: false
            })
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(`Error de Perplexity: ${response.status} - ${errorData.error?.message || response.statusText}`);
        }

        const data = await response.json();
        
        if (!data.choices || !data.choices[0] || !data.choices[0].message) {
            throw new Error('Respuesta inválida de Perplexity');
        }
        
        return data.choices[0].message.content;
        
    } catch (error) {
        console.error('Error de Perplexity:', error);
        throw error;
    }
}

/**
 * UI: Agregar mensaje al chat
 */
function agregarMensaje(texto, tipo) {
    const chatBox = document.getElementById('chat-box');
    const mensaje = document.createElement('div');
    mensaje.className = `mensaje ${tipo}`;
    
    if (tipo === 'loading') {
        mensaje.innerHTML = '<div class="spinner-small"></div> <span>Analizando...</span>';
    } else if (tipo === 'ia') {
        mensaje.innerHTML = `
            <div class="mensaje-header">
                <span class="icono">🤖</span>
                <span class="label">Analista IA</span>
            </div>
            <div class="mensaje-contenido">${formatearTexto(texto)}</div>
        `;
    } else if (tipo === 'usuario') {
        mensaje.innerHTML = `
            <div class="mensaje-header">
                <span class="icono">👤</span>
                <span class="label">Tú</span>
            </div>
            <div class="mensaje-contenido">${texto}</div>
        `;
    } else if (tipo === 'error') {
        mensaje.innerHTML = `
            <div class="mensaje-contenido">⚠️ ${texto}</div>
        `;
    }
    
    chatBox.appendChild(mensaje);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function formatearTexto(texto) {
    return texto
        .split('\n\n')
        .map(parrafo => `<p>${parrafo.trim()}</p>`)
        .join('');
}

/**
 * Event listeners
 */
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('chat-input');
    const btnEnviar = document.getElementById('btn-enviar');
    const formNumero = document.getElementById('form-numero');
    
    // CHAT: Enviar con botón
    btnEnviar.addEventListener('click', analizarChat);
    
    // CHAT: Enviar con Enter
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            analizarChat();
        }
    });
    
    // FORMULARIO: Analizar número específico
    formNumero.addEventListener('submit', async function(e) {
        e.preventDefault();
        const numero = document.getElementById('numero-input').value;
        
        if (numero < 0 || numero > 99) {
            alert('Por favor ingresa un número entre 00 y 99');
            return;
        }
        
        // Agregar al chat como mensaje del usuario
        agregarMensaje(`Analizar el número ${numero}`, 'usuario');
        
        // Ejecutar análisis
        await analizarNumeroEspecifico(numero);
    });
    
    // Mensaje de bienvenida
    agregarMensaje('¡Hola! Puedes usar el formulario de la izquierda para analizar un número específico, o preguntarme libremente aquí. Por ejemplo: "¿Qué números jugar hoy?"', 'ia');
});

/**
 * Analizar número específico desde el formulario
 */
async function analizarNumeroEspecifico(numero) {
    try {
        // Validar API key
        if (!PERPLEXITY_API_KEY || PERPLEXITY_API_KEY === 'TU_API_KEY_AQUI') {
            throw new Error('Configura tu API Key de Perplexity en integracion_perplexity_v4.js línea 8');
        }
        
        // Mostrar loading
        agregarMensaje('Analizando número específico...', 'loading');
        
        // Llamar al PHP
        const response = await fetch(`${PHP_ENDPOINT}?numero=${numero}&modo=numero_especifico`);
        
        if (!response.ok) {
            throw new Error(`Error del servidor: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Error en el análisis');
        }
        
        // Quitar loading
        document.querySelector('.mensaje.loading')?.remove();
        
        // Crear prompt
        const prompt = crearPromptNumeroEspecifico(data.contexto_ia);
        
        // Enviar a Perplexity
        const respuestaIA = await enviarAPerplexity(prompt);
        
        // Mostrar respuesta
        agregarMensaje(respuestaIA, 'ia');
        
        // Limpiar input
        document.getElementById('numero-input').value = '';
        
    } catch (error) {
        document.querySelector('.mensaje.loading')?.remove();
        console.error('Error:', error);
        agregarMensaje(`Error: ${error.message}`, 'error');
    }
}
