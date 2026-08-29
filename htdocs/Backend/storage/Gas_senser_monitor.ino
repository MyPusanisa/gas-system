#include <WiFi.h>
#include <HTTPClient.h>

// ============ แก้ไขตรงนี้ให้ตรงกับการตั้งค่าของคุณ ============
const char* ssid = "ชื่อWiFiบ้านคุณ";           // ชื่อ WiFi
const char* password = "รหัสWiFi";              // รหัส WiFi
const char* serverUrl = "http://192.168.1.100/gas_sensor/gas_sensor_monitor.php?action=add_reading";
// ↑ แก้ IP เป็น IP ของเครื่องที่รัน XAMPP (เช่น 192.168.1.100)
// ============================================================

const int mq2Pin = 34;        // ขาที่ต่อ MQ2 (GPIO34)
const int sampleSize = 10;     // จำนวนครั้งในการอ่าน
int readings[10];              // เก็บค่าที่อ่านได้
int readIndex = 0;             // ตำแหน่งปัจจุบัน

unsigned long lastSendTime = 0;
const unsigned long sendInterval = 1000;  // ส่งทุก 1 วินาที

void setup() {
  Serial.begin(115200);
  delay(1000);
  
  Serial.println();
  Serial.print("Connecting to WiFi");
  
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  
  Serial.println();
  Serial.println("WiFi connected!");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());
  
  // เริ่มต้นค่ารีดดิ้งทั้งหมดเป็น 0
  for (int i = 0; i < sampleSize; i++) {
    readings[i] = 0;
  }
}

void loop() {
  // อ่านค่าจากเซ็นเซอร์ MQ2
  int rawValue = analogRead(mq2Pin);
  
  // เก็บค่าใน buffer
  readings[readIndex] = rawValue;
  readIndex = (readIndex + 1) % sampleSize;
  
  unsigned long now = millis();
  
  // ส่งข้อมูลทุก 1 วินาที
  if (now - lastSendTime >= sendInterval) {
    lastSendTime = now;
    
    // กรองค่าผิดปกติ (ตัดค่ามากสุดและน้อยสุดออก)
    int filteredValue = filterOutliers(readings, sampleSize);
    
    // คำนวณค่าเฉลี่ย
    int averageValue = calculateAverage(readings, sampleSize);
    
    Serial.print("Raw: ");
    Serial.print(rawValue);
    Serial.print(" | Filtered: ");
    Serial.print(filteredValue);
    Serial.print(" | Average: ");
    Serial.println(averageValue);
    
    // ส่งไปยังเซิร์ฟเวอร์
    sendToServer(filteredValue, averageValue);
  }
  
  delay(100);  // อ่านค่าทุก 100ms (10 ครั้งต่อ 1 วินาที)
}

// ฟังก์ชันกรองค่าผิดปกติ (ตัดค่ามากสุดและน้อยสุดออก)
int filterOutliers(int arr[], int size) {
  // ทำสำเนา array เพื่อไม่ให้กระทบข้อมูลเดิม
  int temp[10];
  for (int i = 0; i < size; i++) {
    temp[i] = arr[i];
  }
  
  // เรียงลำดับจากน้อยไปมาก (bubble sort)
  for (int i = 0; i < size - 1; i++) {
    for (int j = i + 1; j < size; j++) {
      if (temp[i] > temp[j]) {
        int tempVal = temp[i];
        temp[i] = temp[j];
        temp[j] = tempVal;
      }
    }
  }
  
  // รวมค่าที่ยกเว้นค่าน้อยสุดและมากสุด
  int sum = 0;
  for (int i = 1; i < size - 1; i++) {
    sum += temp[i];
  }
  
  return sum / (size - 2);
}

// ฟังก์ชันคำนวณค่าเฉลี่ย
int calculateAverage(int arr[], int size) {
  int sum = 0;
  for (int i = 0; i < size; i++) {
    sum += arr[i];
  }
  return sum / size;
}

// ฟังก์ชันส่งข้อมูลไปยังเซิร์ฟเวอร์
void sendToServer(int filteredValue, int averageValue) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    
    // เริ่มการเชื่อมต่อ
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/json");
    
    // สร้าง JSON payload
    String payload = "{\"value\": " + String(averageValue) + ", \"raw_value\": " + String(filteredValue) + "}";
    
    // ส่ง POST request
    int httpResponseCode = http.POST(payload);
    
    // ตรวจสอบผลลัพธ์
    if (httpResponseCode > 0) {
      Serial.print("HTTP Response code: ");
      Serial.println(httpResponseCode);
      
      String response = http.getString();
      Serial.println("Response: " + response);
    } else {
      Serial.print("Error code: ");
      Serial.println(httpResponseCode);
    }
    
    // ปิดการเชื่อมต่อ
    http.end();
  } else {
    Serial.println("WiFi disconnected!");
    
    // พยายามเชื่อมต่อ WiFi ใหม่
    WiFi.reconnect();
  }
}