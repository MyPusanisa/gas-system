const int mq2Pin = 34;
 
void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("\n=== TEST MQ2 SENSOR ===");
  Serial.println("Reading analog values from pin 34...");
}

void loop() {
  int rawValue = analogRead(mq2Pin);
  
  int percentage = map(rawValue, 0, 4095, 0, 100);
  
  Serial.print("Raw: ");
  Serial.print(rawValue);
  Serial.print(" | Percentage: ");
  Serial.print(percentage);
  Serial.println("%");
  
  if (rawValue < 500) {
    Serial.println("Status: 🟢 Normal");
  } else if (rawValue < 1000) {
    Serial.println("Status: 🟡 Warning");
  } else {
    Serial.println("Status: 🔴 DANGER!");
  }
  
  delay(1000);
}