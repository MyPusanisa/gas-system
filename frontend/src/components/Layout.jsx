import { useNavigate, useLocation } from "react-router-dom";
import { useState, useEffect } from "react";
import TopBar from "./TopBar";

function Layout({ children }) {
  const navigate = useNavigate();
  const location = useLocation();
  const role = localStorage.getItem("role");
  const username = localStorage.getItem("username");

  const [gasLevel, setGasLevel] = useState(0);
  const [deliverySuccessItems, setDeliverySuccessItems] = useState([]);

  const fetchTopbarStats = async () => {
    try {
      const res = await fetch("http://localhost/Backend/models/get_topbar_stats.php");
      const text = await res.text();
      try {
        const data = JSON.parse(text);
        if (data && data.success) {
          setGasLevel(data.gasLevel || 0);
          // สร้าง Array ตามจำนวนที่นับได้จาก DB เพื่อส่งให้ TopBar
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
    const interval = setInterval(fetchTopbarStats, 3000); // ดึงข้อมูลใหม่ทุก 3 วินาที
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
    <div style={{ minHeight: "100vh", background: "#0f172a" }}>
      <TopBar
        role={role}
        username={username}
        gasLevel={gasLevel}
        deliverySuccessItems={deliverySuccessItems}
      />

      <div style={{ display: "flex", minHeight: "calc(100vh - 73px)" }}>
        <aside style={asideStyle}>
          <h2 style={{ marginTop: 0, fontSize: "24px" }}>GAS SYS</h2>
          <p style={{ opacity: 0.85, fontSize: "16px", marginBottom: "24px" }}>
            {role} : {username}
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

        <main style={mainStyle}>{children}</main>
      </div>
    </div>
  );
}

const asideStyle = { width: "240px", background: "#0b1324", color: "white", padding: "20px", flexShrink: 0, boxSizing: "border-box" };
const mainStyle = { flex: 1, padding: "30px", color: "white", boxSizing: "border-box" };
const menuButtonStyle = { width: "100%", display: "flex", alignItems: "center", gap: "10px", cursor: "pointer", marginBottom: "16px", padding: "14px 16px", borderRadius: "12px", background: "#1f2937", color: "white", border: "none", fontSize: "18px", fontWeight: "500", textAlign: "left" };
const logoutButtonStyle = { marginTop: "30px", padding: "12px 14px", border: "none", borderRadius: "10px", background: "#ef4444", color: "white", cursor: "pointer", width: "100%", fontSize: "16px", fontWeight: "500" };

export default Layout;