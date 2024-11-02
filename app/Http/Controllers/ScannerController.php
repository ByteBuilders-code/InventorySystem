<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class ScannerController extends Controller
{
    public function iniciar(Request $request)
    {
        $opcion = $request->input('opcion');
        
        try {
            $pythonScript = base_path('python_scanner/barcode_scanner.py');
            
            if (!file_exists($pythonScript)) {
                throw new \Exception("Script de Python no encontrado en: " . $pythonScript);
            }

            $pythonPath = 'C:\Windows\py.exe';
            
            $process = new Process([$pythonPath, $pythonScript, $opcion]);
            $process->setTimeout(null);
            $process->setOptions(['create_new_console' => true]);
            
            // Iniciar el proceso en segundo plano
            $process->start();

            return response()->json([
                'status' => 'success',
                'message' => 'Scanner iniciado correctamente',
                'pid' => $process->getPid()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en el scanner: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function ultimoCodigo()
    {
        try {
            $archivo = storage_path('app/ultimo_codigo.txt');
            
            \Log::info('Ruta del archivo: ' . $archivo);
            
            if (!file_exists($archivo)) {
                \Log::info('El archivo no existe');
                return response()->json([
                    'status' => 'success',
                    'codigo' => null
                ]);
            }

            $codigo = file_get_contents($archivo);
            \Log::info('Contenido del archivo: "' . $codigo . '"');
            
            if (empty(trim($codigo))) {
                \Log::info('El archivo está vacío');
                return response()->json([
                    'status' => 'success',
                    'codigo' => null
                ]);
            }
            
            // Limpiar el archivo después de leerlo
            file_put_contents($archivo, '');
            \Log::info('Archivo limpiado después de leer');
            
            $codigo = trim($codigo);
            \Log::info('Código a devolver: "' . $codigo . '"');
            
            return response()->json([
                'status' => 'success',
                'codigo' => $codigo
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al leer el último código: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function detener(Request $request)
    {
        try {
            $pid = $request->input('pid');
            
            if (!$pid) {
                throw new \Exception('No se proporcionó PID del proceso');
            }

            if (PHP_OS_FAMILY === 'Windows') {
                // Para Windows, usando chcp 65001 para UTF-8
                $command = "chcp 65001 > nul && taskkill /F /PID $pid 2>&1";
                $output = [];
                $returnCode = 0;
                
                exec($command, $output, $returnCode);
                
                // Convertir la salida a UTF-8
                $output = array_map(function($line) {
                    return mb_convert_encoding($line, 'UTF-8', 'auto');
                }, $output);
                
            } else {
                // Para Linux/Unix
                exec("kill -9 $pid 2>&1", $output, $returnCode);
            }

            if ($returnCode !== 0) {
                $errorMessage = mb_convert_encoding(implode("\n", $output), 'UTF-8', 'auto');
                throw new \Exception('No se pudo detener el proceso: ' . $errorMessage);
            }

            // Registrar en el log para debug
            \Log::info('Proceso detenido exitosamente', [
                'pid' => $pid,
                'output' => $output,
                'returnCode' => $returnCode
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Scanner detenido correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al detener el scanner: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto')
            ], 500);
        }
    }
}
