<!DOCTYPE html>
<html>
<head>
    <title>Scanner</title>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        div {
            text-align: center;
            margin-top: 20px;
        }
        
        button {
            padding: 10px 20px;
            margin: 10px;
            font-size: 16px;
            cursor: pointer;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
        }
        
        button:hover {
            background-color: #45a049;
        }

        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .scanner-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .scan-input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            font-size: 18px;
            border: 2px solid #4CAF50;
            border-radius: 4px;
        }

        .scan-history {
            width: 100%;
            height: 200px;
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            font-family: monospace;
        }

        #status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }

        .error {
            background-color: #f2dede;
            color: #a94442;
        }

        .stop-button {
            background-color: #dc3545;
            margin-top: 20px;
        }

        .stop-button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="scanner-container">
        <h2>Seleccione el método de lectura:</h2>
        <button onclick="iniciarScanner('1')">Usar Cámara</button>
        <button onclick="iniciarScanner('2')">Usar Scanner de Códigos</button>
        <div id="status"></div>

        <div class="scan-section" style="display: none;">
            <button onclick="detenerScanner()" class="stop-button">Detener Scanner</button>
            <h3>Último código escaneado:</h3>
            <input type="text" id="lastScan" class="scan-input" readonly>
            
            <h3>Historial de escaneos:</h3>
            <textarea id="scanHistory" class="scan-history" readonly></textarea>
        </div>
    </div>

    <script>
        let scannerPID = null;
        let pollingInterval = null;

        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

        async function iniciarScanner(opcion) {
            try {
                document.getElementById('status').innerHTML = 'Iniciando scanner...';
                document.getElementById('status').className = '';
                
                const response = await axios.post('/api/scanner/iniciar', { opcion }, {
                    timeout: 5000
                });
                
                if (response.data.status === 'success') {
                    scannerPID = response.data.pid;
                    document.getElementById('status').innerHTML = 'Scanner iniciado correctamente. ' + 
                        'Puedes empezar a escanear productos.';
                    document.getElementById('status').className = 'success';
                    
                    // Mostrar la sección de escaneo
                    document.querySelector('.scan-section').style.display = 'block';
                    
                    // Deshabilitar los botones de inicio
                    document.querySelectorAll('button:not(.stop-button)').forEach(button => {
                        button.disabled = true;
                    });

                    // Iniciar el polling
                    iniciarPolling();
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('status').innerHTML = 'Error al iniciar el scanner: ' + 
                    (error.response?.data?.message || error.message);
                document.getElementById('status').className = 'error';
            }
        }

        async function detenerScanner() {
            try {
                if (scannerPID) {
                    const response = await axios.post('/api/scanner/detener', { pid: scannerPID });
                    if (response.data.status === 'success') {
                        document.getElementById('status').innerHTML = 'Scanner detenido correctamente';
                        document.getElementById('status').className = 'success';
                        
                        // Detener el polling
                        if (pollingInterval) {
                            clearInterval(pollingInterval);
                        }
                        
                        // Habilitar los botones de inicio
                        document.querySelectorAll('button:not(.stop-button)').forEach(button => {
                            button.disabled = false;
                        });
                        
                        // Ocultar la sección de escaneo
                        document.querySelector('.scan-section').style.display = 'none';
                        
                        scannerPID = null;
                    }
                }
            } catch (error) {
                console.error('Error al detener el scanner:', error);
                document.getElementById('status').innerHTML = 'Error al detener el scanner: ' + 
                    (error.response?.data?.message || error.message);
                document.getElementById('status').className = 'error';
            }
        }

        function agregarEscaneo(codigo) {
            if (!codigo) return;
            
            console.log('Agregando código:', codigo); // Debug
            
            const timestamp = new Date().toLocaleTimeString();
            const lastScanInput = document.getElementById('lastScan');
            const scanHistory = document.getElementById('scanHistory');
            
            // Efecto visual para el último escaneo
            lastScanInput.value = codigo;
            lastScanInput.style.backgroundColor = '#90EE90';
            setTimeout(() => {
                lastScanInput.style.backgroundColor = '';
            }, 500);
            
            // Agregar al historial
            scanHistory.value = `[${timestamp}] ${codigo}\n` + scanHistory.value;
        }

        function iniciarPolling() {
            pollingInterval = setInterval(async () => {
                try {
                    const response = await axios.get('/api/scanner/ultimo-codigo');
                    console.log('Respuesta polling:', response.data); // Debug
                    
                    if (response.data.status === 'success' && response.data.codigo) {
                        console.log('Código recibido:', response.data.codigo);
                        agregarEscaneo(response.data.codigo);
                    }
                } catch (error) {
                    console.error('Error en polling:', error);
                }
            }, 1000);
        }

        function actualizarEstadoScanner(mensaje, tipo = 'success') {
            const status = document.getElementById('status');
            status.innerHTML = mensaje;
            status.className = tipo;
            
            if (tipo === 'success') {
                status.innerHTML += '<br><small>Esperando códigos...</small>';
            }
        }
    </script>
</body>
</html> 