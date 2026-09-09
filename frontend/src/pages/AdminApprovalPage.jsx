import React, { useState, useEffect } from "react";
import Layout from "../components/Layout";
import { API_BASE } from "../config"; // 👈 นำเข้าตัวแปร API_BASE จากไฟล์ config ของคุณ

export default function AdminApprovalPage() {
  const [pendingList, setPendingList] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchPendingDeliveries();
  }, []);

  const fetchPendingDeliveries = async () => {
    setLoading(true);
    try {
      const res = await fetch(`${API_BASE}/delivery/get_pending.php`);
      const data = await res.json();
      if (data.success) {
        setPendingList(data.data);
      } else {
        alert(data.message || "ไม่สามารถดึงข้อมูลรายการรออนุมัติได้");
      }
    } catch (err) {
      console.error(err);
      alert("เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์");
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (deliveryId) => {
  if (!confirm(`ยืนยันการอนุมัติงานจัดส่ง รหัส #${deliveryId} ใช่หรือไม่?`)) return;

  try {
    const res = await fetch(`${API_BASE}/delivery/update_status.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "approve",
        delivery_id: deliveryId
      })
    });
    
    const data = await res.json();

    if (data.success) {
      alert("อนุมัติงานจัดส่งเรียบร้อยแล้ว");
      fetchPendingDeliveries(); // ดึงข้อมูลใหม่เพื่อรีเฟรชหน้าจอ
    } else {
      alert(data.message || "เกิดข้อผิดพลาดในการอนุมัติ");
    }
  } catch (err) {
    console.error(err);
    alert("เกิดข้อผิดพลาดในการสื่อสารกับเซิร์ฟเวอร์");
  }
};

  return (
    <Layout>
      <div style={{ maxWidth: "1000px", margin: "0 auto" }}>
        <h2 style={{ fontSize: "24px", marginBottom: "20px" }}>อนุมัติการจัดส่ง (Admin Approval)</h2>

        {loading ? (
          <div>กำลังโหลดข้อมูล...</div>
        ) : pendingList.length === 0 ? (
          <div style={{ padding: "30px", background: "#1f2937", borderRadius: "12px", textAlign: "center" }}>
            ไม่มีรายการที่รออนุมัติในขณะนี้
          </div>
        ) : (
          <div style={{ display: "flex", flexDirection: "column", gap: "20px" }}>
            {pendingList.map((item) => (
              <div
                key={item.delivery_id}
                style={{
                  background: "#1f2937",
                  borderRadius: "12px",
                  padding: "20px",
                  display: "grid",
                  gridTemplateColumns: "1fr 260px",
                  gap: "20px"
                }}
              >
                <div>
                  <h3 style={{ marginTop: 0 }}>รหัสงาน: #{item.delivery_id}</h3>
                  <p><strong>ลูกค้า:</strong> {item.customer_name} ({item.phone})</p>
                  <p><strong>ที่อยู่:</strong> {item.address}</p>
                  <p><strong>ประเภทแก๊ส:</strong> {item.brand} | {item.gas_type} | {item.size}</p>
                  <p><strong>ผู้จัดส่ง:</strong> {item.staff_name || item.staff_id}</p>

                  <button
                    onClick={() => handleApprove(item.delivery_id)}
                    style={{
                      marginTop: "15px",
                      padding: "10px 20px",
                      background: "#22c55e",
                      color: "#fff",
                      border: "none",
                      borderRadius: "8px",
                      cursor: "pointer",
                      fontWeight: "bold",
                      fontSize: "16px"
                    }}
                  >
                    อนุมัติงานจัดส่ง
                  </button>
                </div>

                <div>
                  <p style={{ fontWeight: "bold", marginTop: 0, marginBottom: "8px" }}>รูปหลักฐานจัดส่ง:</p>
                  {item.proof_image_path ? (
                    <img
                      src={`${API_BASE.replace('/models', '')}/uploads/${item.proof_image_path}`}
                      alt="หลักฐาน"
                      style={{
                        width: "100%",
                        height: "180px",
                        objectFit: "cover",
                        borderRadius: "8px",
                        border: "1px solid #374151"
                      }}
                    />
                  ) : (
                    <div
                      style={{
                        height: "180px",
                        background: "#0f172a",
                        borderRadius: "8px",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        color: "#64748b"
                      }}
                    >
                      ไม่มีรูปภาพ
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </Layout>
  );
}