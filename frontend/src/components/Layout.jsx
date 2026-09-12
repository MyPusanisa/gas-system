import { useNavigate, useLocation } from "react-router-dom";
import { useState, useEffect } from "react";
import TopBar from "./TopBar";

// ฟังก์ชันดึงชื่อผู้ใช้จาก LocalStorage แบบรองรับหลาย Key (userName, username, name, user JSON)
const getStoredUsername = () => {
  let storedUsername =
    localStorage.getItem("userName") ||
    localStorage.getItem("username") ||
    localStorage.getItem("name") ||
    "";

  if (!storedUsername) {
    try {
      const userObj = JSON.parse(localStorage.getItem("user") || "{}");
      storedUsername = userObj.name || userObj.username || userObj.userName || "";
    } catch (e) {
      storedUsername = "";
    }
  }
  return storedUsername;
};

function Layout({ children }) {
  const navigate = useNavigate();
  const location = useLocation();

  const [role, setRole] = useState(localStorage.getItem("role") || "");
  const [username, setUsername] = useState(getStoredUsername());

  const [gasLevel, setGasLevel] = useState(0);
  const [deliverySuccessItems, setDeliverySuccessItems] = useState([]);
  
  // State สำหรับเปิด/ปิด Sidebar บนหน้าจอมือถือ
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  // อัปเดตข้อมูลผู้ใช้ทุกครั้งที่มีการเปลี่ยนหน้า
  useEffect(() => {
    setRole(localStorage.getItem("role") || "");
    setUsername(getStoredUsername());
    setIsMobileMenuOpen(false); // ปิดเมนูป๊อปอัพเมื่อกดเปลี่ยนหน้า
  }, [location.pathname]);

  const fetchTopbarStats = async () => {
    try {
      const res = await fetch("/Backend/models/get_topbar_stats.php", {
        credentials: "include",
      });
      const text = await res.text();
      try {
        const data = JSON.parse(text);
        if (data && data.success) {
          setGasLevel(data.gasLevel || 0);
          const count = data.successCount || 0;
          setDeliverySuccessItems(new Array(count).fill(1));
        }
      } catch (jsonErr) {
        console.warn("JSON parse error at get_topbar_stats.php:", text);
      }
    } catch (err) {
      console.error("Fetch error at get_topbar_stats.php:", err);
    }
  };

  useEffect(() => {
    fetchTopbarStats();
    const interval = setInterval(fetchTopbarStats, 3000);
    return () => clearInterval(interval);
  }, []);

  const handleLogout = () => {
    localStorage.clear();
    navigate("/");
  };

  const getMenuButtonStyle = (path) => ({
    ...menuButtonStyle,
    background: location.pathname === path ? "#334155" : "#1f2937",
  });

  return (
    <div style={{ minHeight: "100vh", background: "#0f172a", display: "flex", flexDirection: "column" }}>
      <TopBar
        role={role}
        username={username}
        gasLevel={gasLevel}
        deliverySuccessItems={deliverySuccessItems}
      />

      {/* แถบเปิดเมนูสำหรับอุปกรณ์มือถือ */}
      <div style={mobileHeaderBarContainerStyle}>
        <button
          onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
          style={mobileMenuToggleBtnStyle}
        >
          {isMobileMenuOpen ? "✕ ปิดเมนู" : "☰ เมนูหลัก"}
        </button>
        <span style={{ color: "white", fontSize: "14px", fontWeight: "bold" }}>
          GAS SYS ({role || "staff"})
        </span>
      </div>

      <div style={layoutBodyStyle}>
        {/* Sidebar Navigation */}
        <aside style={{
          ...asideStyle,
          display: isMobileMenuOpen ? "block" : undefined
        }} className="responsive-sidebar">
          <h2 style={{ marginTop: 0, fontSize: "24px" }}>GAS SYS</h2>
          <p style={{ opacity: 0.85, fontSize: "16px", marginBottom: "24px" }}>
            {role || "staff"} : {username || "ไม่ระบุชื่อ"}
          </p>

          <div style={{ marginTop: "10px" }}>
            {role === "admin" && (
              <>
                <button type="button" style={getMenuButtonStyle("/dashboard")} onClick={() => navigate("/dashboard")}>
                  Dashboard
                </button>
                <button type="button" style={getMenuButtonStyle("/gas")} onClick={() => navigate("/gas")}>
                  ถังแก๊ส
                </button>
                <button type="button" style={getMenuButtonStyle("/maintenance")} onClick={() => navigate("/maintenance")}>
                  Maintenance
                </button>
                <button type="button" style={getMenuButtonStyle("/staff")} onClick={() => navigate("/staff")}>
                  พนักงานส่ง
                </button>
                <button type="button" style={getMenuButtonStyle("/approval")} onClick={() => navigate("/approval")}>
                  อนุมัติการจัดส่ง
                </button>
              </>
            )}
            <button type="button" style={getMenuButtonStyle("/delivery")} onClick={() => navigate("/delivery")}>
              Delivery
            </button>
          </div>

          <button onClick={handleLogout} style={logoutButtonStyle}>
            Logout
          </button>
        </aside>

        {/* ส่วนแสดงผลเนื้อหาหลัก */}
        <main style={mainStyle}>{children}</main>
      </div>

      {/* Style แทรก responsive media query */}
      <style>{`
        @media (max-width: 768px) {
          .responsive-sidebar {
            display: ${isMobileMenuOpen ? "block" : "none"} !important;
            width: 100% !important;
            min-width: 100% !important;
            box-sizing: border-box !important;
          }
        }
        @media (min-width: 769px) {
          .responsive-sidebar {
            display: block !important;
          }
        }
      `}</style>
    </div>
  );
}

const layoutBodyStyle = {
  display: "flex",
  flex: 1,
  minHeight: "calc(100vh - 73px)",
  flexWrap: "wrap",
};

const mobileHeaderBarContainerStyle = {
  display: "flex",
  justifyContent: "space-between",
  alignItems: "center",
  padding: "10px 16px",
  background: "#1e293b",
  borderBottom: "1px solid #334155",
};

const mobileMenuToggleBtnStyle = {
  background: "#3b82f6",
  color: "white",
  border: "none",
  padding: "8px 14px",
  borderRadius: "6px",
  fontSize: "14px",
  fontWeight: "bold",
  cursor: "pointer",
};

const asideStyle = { 
  width: "240px", 
  background: "#0b1324", 
  color: "white", 
  padding: "20px", 
  flexShrink: 0, 
  boxSizing: "border-box" 
};

const mainStyle = { 
  flex: 1, 
  padding: "16px", 
  color: "white", 
  boxSizing: "border-box", 
  width: "100%", 
  maxWidth: "100vw", 
  overflowX: "hidden" 
};

const menuButtonStyle = { 
  width: "100%", 
  display: "flex", 
  alignItems: "center", 
  gap: "10px", 
  cursor: "pointer", 
  marginBottom: "16px", 
  padding: "14px 16px", 
  borderRadius: "12px", 
  background: "#1f2937", 
  color: "white", 
  border: "none", 
  fontSize: "18px", 
  fontWeight: "500", 
  textAlign: "left" 
};

const logoutButtonStyle = { 
  marginTop: "30px", 
  padding: "12px 14px", 
  border: "none", 
  borderRadius: "10px", 
  background: "#ef4444", 
  color: "white", 
  cursor: "pointer", 
  width: "100%", 
  fontSize: "16px", 
  fontWeight: "500" 
};

export default Layout;