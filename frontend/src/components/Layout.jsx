import { useNavigate, useLocation } from "react-router-dom";
import { useState, useEffect } from "react";
import {
  LayoutDashboard,
  Cylinder,
  Wrench,
  Users,
  ClipboardCheck,
  Truck,
  LogOut,
  Menu,
  X,
  Flame,
} from "lucide-react";
import TopBar from "./TopBar";

const ADMIN_MENU = [
  { path: "/dashboard", label: "Dashboard", icon: LayoutDashboard },
  { path: "/gas", label: "ถังแก๊ส", icon: Cylinder },
  { path: "/maintenance", label: "ตรวจบำรุง", icon: Wrench },
  { path: "/staff", label: "พนักงานส่ง", icon: Users },
  { path: "/approval", label: "อนุมัติการจัดส่ง", icon: ClipboardCheck },
];
const COMMON_MENU = [{ path: "/delivery", label: "Delivery", icon: Truck }];

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

  const isActive = (path) => location.pathname === path || location.pathname.startsWith(`${path}/`);
  const menu = role === "admin" ? [...ADMIN_MENU, ...COMMON_MENU] : COMMON_MENU;

  return (
    <div style={{ minHeight: "100vh", background: "#0f172a" }}>
      {/* แถบเปิดเมนูสำหรับอุปกรณ์มือถือ */}
      <div style={mobileHeaderBarContainerStyle} className="mobile-menu-bar">
        <button
          onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
          style={mobileMenuToggleBtnStyle}
        >
          {isMobileMenuOpen ? <X size={16} /> : <Menu size={16} />}
          {isMobileMenuOpen ? "ปิดเมนู" : "เมนู"}
        </button>
        <span style={{ color: "white", fontSize: "14px", fontWeight: 600 }}>GAS SYS</span>
      </div>

      <div style={layoutBodyStyle} className="layout-body">
        {/* Sidebar Navigation */}
        <aside style={{
          ...asideStyle,
          display: isMobileMenuOpen ? "flex" : undefined
        }} className="responsive-sidebar">
          <div style={brandStyle}>
            <span style={brandIconStyle}><Flame size={20} /></span>
            <div>
              <div style={{ fontSize: "18px", fontWeight: 700, lineHeight: 1.1 }}>GAS SYS</div>
              <div style={{ fontSize: "12px", color: "#9ca3af" }}>ระบบจัดการถังแก๊ส</div>
            </div>
          </div>

          <nav style={{ display: "flex", flexDirection: "column", gap: "4px" }}>
            <div style={navSectionLabelStyle}>เมนู</div>
            {menu.map(({ path, label, icon: Icon }) => {
              const active = isActive(path);
              return (
                <button
                  key={path}
                  type="button"
                  onClick={() => navigate(path)}
                  style={{ ...menuButtonStyle, ...(active ? menuActiveStyle : {}) }}
                >
                  <Icon size={18} strokeWidth={active ? 2.4 : 2} />
                  {label}
                </button>
              );
            })}
          </nav>

          <button onClick={handleLogout} style={logoutButtonStyle}>
            <LogOut size={18} />
            ออกจากระบบ
          </button>
        </aside>

        {/* คอลัมน์ขวา: TopBar + เนื้อหาหลัก */}
        <div style={contentColumnStyle}>
          <TopBar
            role={role}
            username={username}
            gasLevel={gasLevel}
            deliverySuccessItems={deliverySuccessItems}
          />
          <main style={mainStyle}>{children}</main>
        </div>
      </div>

      {/* Style แทรก responsive media query */}
      <style>{`
        @media (max-width: 768px) {
          .layout-body {
            flex-direction: column;
          }
          .responsive-sidebar {
            display: ${isMobileMenuOpen ? "flex" : "none"} !important;
            width: 100% !important;
            min-width: 100% !important;
            box-sizing: border-box !important;
          }
        }
        @media (min-width: 769px) {
          .responsive-sidebar {
            display: flex !important;
            position: sticky;
            top: 0;
            height: 100vh;
          }
          /* แถบเปิดเมนูใช้เฉพาะบนมือถือ */
          .mobile-menu-bar {
            display: none !important;
          }
        }
      `}</style>
    </div>
  );
}

const layoutBodyStyle = {
  display: "flex",
  minHeight: "100vh",
};

const contentColumnStyle = {
  flex: 1,
  minWidth: 0,
  display: "flex",
  flexDirection: "column",
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
  display: "inline-flex",
  alignItems: "center",
  gap: "6px",
  background: "#2563eb",
  color: "white",
  border: "none",
  padding: "8px 14px",
  borderRadius: "8px",
  fontSize: "14px",
  fontWeight: 600,
  cursor: "pointer",
};

const asideStyle = {
  width: "240px",
  background: "#0b1324",
  borderRight: "1px solid #1f2937",
  color: "white",
  padding: "20px 14px",
  flexShrink: 0,
  boxSizing: "border-box",
  flexDirection: "column",
  gap: "20px",
};

const brandStyle = { display: "flex", alignItems: "center", gap: "10px", padding: "4px 8px 8px" };
const brandIconStyle = {
  width: "38px",
  height: "38px",
  borderRadius: "10px",
  background: "linear-gradient(135deg, #f97316, #ef4444)",
  display: "flex",
  alignItems: "center",
  justifyContent: "center",
  color: "white",
};

const navSectionLabelStyle = { fontSize: "11px", color: "#6b7280", textTransform: "uppercase", letterSpacing: "0.08em", padding: "0 10px 6px" };

const mainStyle = {
  flex: 1,
  padding: "24px",
  color: "white",
  boxSizing: "border-box",
  width: "100%",
  maxWidth: "100vw",
  overflowX: "hidden",
  minWidth: 0,
};

const menuButtonStyle = {
  width: "100%",
  display: "flex",
  alignItems: "center",
  gap: "12px",
  cursor: "pointer",
  padding: "11px 12px",
  borderRadius: "10px",
  background: "transparent",
  color: "#cbd5e1",
  border: "none",
  fontSize: "15px",
  fontWeight: 500,
  textAlign: "left",
};

const menuActiveStyle = {
  background: "rgba(59,130,246,0.15)",
  color: "white",
  boxShadow: "inset 3px 0 0 #3b82f6",
  fontWeight: 600,
};

const logoutButtonStyle = {
  marginTop: "auto",
  display: "flex",
  alignItems: "center",
  justifyContent: "center",
  gap: "8px",
  padding: "11px 14px",
  border: "1px solid #7f1d1d",
  borderRadius: "10px",
  background: "rgba(239,68,68,0.1)",
  color: "#fca5a5",
  cursor: "pointer",
  width: "100%",
  fontSize: "15px",
  fontWeight: 500,
};

export default Layout;