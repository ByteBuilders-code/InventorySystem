# -*- coding: utf-8 -*-
import cv2
import pyzbar.pyzbar as pyzbar
import requests
import numpy as np
import threading
import time
import sys
import keyboard
import os
import json

# Al inicio del script
import sys
sys.stdout.reconfigure(encoding='utf-8')
sys.stderr.reconfigure(encoding='utf-8')

def main(opcion=None):
    try:
        # Obtener la opción de los argumentos de línea de comandos
        if opcion is None and len(sys.argv) > 1:
            opcion = sys.argv[1]
        
        # Si aún no hay opción, solicitar entrada
        if opcion is None:
            print("Presiona 'x' en cualquier momento para salir del programa")
            print("Seleccione el método de lectura:")
            print("1. Cámara")
            print("2. Lector de códigos de barras")
            opcion = input("Ingrese su opción (1 o 2): ")
        
        # Iniciar thread para monitorear salida
        thread_salida = threading.Thread(target=monitorear_salida)
        thread_salida.daemon = True
        thread_salida.start()

        if str(opcion) == "1":
            print(json.dumps({"status": "success", "message": "Iniciando cámara..."}))
            usar_camara()
        elif str(opcion) == "2":
            print(json.dumps({"status": "success", "message": "Iniciando scanner..."}))
            usar_scanner()
        else:
            print(json.dumps({"status": "error", "message": "Opción no válida"}))
            return

    except Exception as e:
        print(json.dumps({
            "status": "error",
            "message": str(e)
        }))
        return

def monitorear_salida():
    while True:
        try:
            if keyboard.is_pressed('x') or keyboard.is_pressed('ctrl+c'):
                print("\nSaliendo del programa...")
                os._exit(0)  # Forzar la salida del programa
            time.sleep(0.1)
        except:
            pass

def obtener_entrada_scanner():
    """
    Captura la entrada del scanner de códigos de barras USB
    """
    codigo = ''
    print("Esperando entrada del scanner...") # Debug
    
    while True:
        try:
            evento = keyboard.read_event(suppress=True)
            if evento.event_type == keyboard.KEY_DOWN:
                if evento.name == 'enter':
                    if codigo:
                        print(f"Código capturado: {codigo}") # Debug
                        return codigo.strip()
                    codigo = ''
                elif evento.name == 'x' or evento.name == 'ctrl+c':
                    print("Saliendo del programa...")
                    return None
                else:
                    codigo += evento.name
                    print(f"Caracter capturado: {evento.name}") # Debug
                
        except Exception as e:
            print(f"Error en obtener_entrada_scanner: {e}")
            return None

def usar_scanner():
    print("Iniciando modo scanner...")  # Debug
    print("Esperando códigos de barras...")
    
    while True:
        try:
            codigo = obtener_entrada_scanner()
            if codigo:
                print(f"Código escaneado: {codigo}")  # Debug
                enviar_codigo(codigo)
            
            time.sleep(0.1)  # Pequeña pausa para no saturar el CPU
            
        except Exception as e:
            print(f"Error en usar_scanner: {e}")
            break

def get_storage_path():
    # Ruta absoluta para Windows
    base_path = os.path.abspath(os.path.dirname(os.path.dirname(__file__)))
    storage_path = os.path.join(base_path, 'storage', 'app')
    
    print(f"Base path: {base_path}")  # Debug
    print(f"Storage path: {storage_path}")  # Debug
    
    # Crear el directorio si no existe
    os.makedirs(storage_path, exist_ok=True)
    
    file_path = os.path.join(storage_path, 'ultimo_codigo.txt')
    print(f"File path: {file_path}")  # Debug
    
    return file_path

def enviar_codigo(barcode_data):
    try:
        archivo = get_storage_path()
        print(f"Intentando guardar código: {barcode_data} en {archivo}")  # Debug
        
        # Asegurarse de que el directorio existe
        os.makedirs(os.path.dirname(archivo), exist_ok=True)
        
        # Guardar el código con timestamp
        timestamp = time.strftime("%Y-%m-%d %H:%M:%S")
        with open(archivo, 'w', encoding='utf-8') as f:
            f.write(f"{barcode_data}")
        
        print(f"Código guardado exitosamente: {barcode_data}")  # Debug
        
        # Verificar que el archivo se creó correctamente
        if os.path.exists(archivo):
            with open(archivo, 'r', encoding='utf-8') as f:
                contenido = f.read()
                print(f"Verificación - Contenido del archivo: {contenido}")  # Debug
        
    except Exception as e:
        print(f"Error al guardar el código: {str(e)}")
        print(json.dumps({
            "status": "error",
            "message": str(e)
        }))

def usar_camara():
    # Mover aquí todo el código existente de la función main relacionado con la cámara
    cap = cv2.VideoCapture(0)
    cap.set(3, 640)
    cap.set(4, 480)
    
    print("Presiona 'q' para cerrar la cámara")

    # Variable para controlar el tiempo entre lecturas
    ultimo_escaneo = 0
    tiempo_espera = 2  # Segundos entre escaneos

    while True:
        try:
            success, frame = cap.read()
            if not success:
                print("Error al capturar el video")
                break

            tiempo_actual = time.time()
            
            # Verificar si ha pasado suficiente tiempo desde el último escaneo
            if tiempo_actual - ultimo_escaneo >= tiempo_espera:
                try:
                    # Convertir la imagen a escala de grises para mejor detección
                    gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
                    barcodes = pyzbar.decode(gray)
                except Exception as e:
                    print(f"Error al decodificar: {e}")
                    continue
                
                if len(barcodes) > 0:
                    for barcode in barcodes:
                        # Mejorar el manejo de códigos PDF417
                        if barcode.type == 'PDF417':
                            print("Código PDF417 detectado - omitiendo para evitar errores")
                            continue
                            
                        try:
                            barcode_data = barcode.data.decode("utf-8")
                            print(f'Código de barras detectado | Tipo: {barcode.type} | Data: {barcode_data}')
                            
                            # Enviar el código a Laravel en un hilo separado
                            thread = threading.Thread(target=enviar_codigo, args=(barcode_data,))
                            thread.daemon = True
                            thread.start()
                            
                            # Actualizar tiempo del último escaneo
                            ultimo_escaneo = tiempo_actual
                            
                            # Obtener los puntos del polígono que rodea el código de barras
                            points = barcode.polygon
                            
                            if len(points) > 0:
                                # Convertir puntos a numpy array
                                pts = np.array(points, np.int32)
                                pts = pts.reshape((-1, 1, 2))
                                
                                # Dibujar el polígono ajustado alrededor del código de barras
                                cv2.polylines(frame, [pts], True, (0, 255, 0), 2)

                                # Mostrar datos del código de barras
                                x = points[0].x
                                y = points[0].y
                                cv2.putText(frame, barcode_data, (x, y - 10),
                                           cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 2)
                        except Exception as e:
                            print(f"Error al procesar código de barras: {e}")
                            continue

            # Mostrar frame
            cv2.imshow('Lector de Códigos de Barras', frame)

            # Salir con 'q' o 'x'
            if cv2.waitKey(1) & 0xFF in [ord('q'), ord('x')] or keyboard.is_pressed('ctrl+c'):
                break
                
        except Exception as e:
            print(f"Error: {e}")
            break
    
def iniciar_scanner_desde_api(opcion):
    try:
        main(opcion)
        return {"status": "success", "message": "Scanner iniciado correctamente"}
    except Exception as e:
        return {"status": "error", "message": str(e)}

    
    cap.release()
    cv2.destroyAllWindows()
    sys.exit(0)

if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        print(json.dumps({
            "status": "error",
            "message": str(e)
        }))
