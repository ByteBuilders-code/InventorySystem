import cv2

cap = cv2.VideoCapture(0)  # Intenta abrir la cámara con índice 0
if not cap.isOpened():
    print("No se puede abrir la cámara")
    exit()

while True:
    ret, frame = cap.read()
    if not ret:
        print("No se pudo recibir la imagen. Saliendo...")
        break

    cv2.imshow('Camara', frame)
    
    # Presiona 'q' para salir del bucle
    if cv2.waitKey(1) == ord('q'):
        break

cap.release()
cv2.destroyAllWindows()
