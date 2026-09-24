import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { fetchAPI } from "../services/api";

function Login() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleLogin = async (e) => {
    e.preventDefault();

    if (!username.trim() || !password) {
      setError("กรุณากรอก Username และ Password ให้ครบถ้วน");
      return;
    }

    setError("");
    setLoading(true);

    try {
      const data = await fetchAPI("/login.php", {
        method: "POST",
        body: JSON.stringify({
          username: username.trim(),
          password: password,
        }),
      });

      if (data && data.success) {
        const role = String(data.role || "").trim().toLowerCase();

        localStorage.setItem("isLoggedIn", "true");
        localStorage.setItem("role", role);
        localStorage.setItem("name", data.name || "");
        localStorage.setItem("username", data.username || username.trim());
        localStorage.setItem("admin_id", data.admin_id || "");
        localStorage.setItem("staff_id", data.staff_id || "");

        if (role === "admin") {
          navigate("/dashboard");
        } else if (role === "staff") {
          navigate("/delivery");
        } else {
          setError("ไม่พบสิทธิ์การใช้งานของคุณในระบบ");
          localStorage.clear();
        }
      } else {
        setError(data?.message || "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง");
      }
    } catch (err) {
      setError("ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้ กรุณาลองใหม่อีกครั้ง");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={containerStyle}>
      <div style={cardStyle}>
        {/* โลโก้แก๊ส */}
        <div style={logoWrapperStyle}>
          <div style={logoCircleStyle}>
            <span style={{ fontSize: "28px" }}>🔥</span>
          </div>
        </div>

        <h2 style={titleStyle}>Gas Management System</h2>
        <p style={subtitleStyle}>เข้าสู่ระบบเพื่อจัดการคลังและระบบจัดส่งแก๊ส</p>

        <form onSubmit={handleLogin}>
          {/* ช่อง Username */}
          <div style={inputGroupStyle}>
            <label style={labelStyle}>ชื่อผู้ใช้งาน (Username)</label>
            <input
              type="text"
              placeholder="กรอกชื่อผู้ใช้ เช่น somchai หรือ admin"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              style={inputStyle}
              disabled={loading}
            />
          </div>

          {/* ช่อง Password + ปุ่มซ่อน/แสดงรหัสผ่าน */}
          <div style={inputGroupStyle}>
            <label style={labelStyle}>รหัสผ่าน (Password)</label>
            <div style={{ position: "relative" }}>
              <input
                type={showPassword ? "text" : "password"}
                placeholder="กรอกรหัสผ่าน"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                style={{ ...inputStyle, paddingRight: "40px" }}
                disabled={loading}
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                style={togglePasswordStyle}
              >
                {showPassword ? "🙈" : "👁️"}
              </button>
            </div>
          </div>

          {/* ข้อความแจ้งเตือน Error */}
          {error && <div style={errorStyle}>⚠️ {error}</div>}

          {/* ปุ่ม Login */}
          <button type="submit" style={buttonStyle} disabled={loading}>
            {loading ? "กำลังเข้าสู่ระบบ..." : "เข้าสู่ระบบ"}
          </button>
        </form>
      </div>
    </div>
  );
}

const containerStyle = {
  display: "flex",
  justifyContent: "center",
  alignItems: "center",
  minHeight: "100vh",
  backgroundColor: "#f1f5f9",
  fontFamily: "'Kanit', 'Prompt', sans-serif",
};

const cardStyle = {
  width: "100%",
  maxWidth: "420px",
  padding: "36px 32px",
  backgroundColor: "#ffffff",
  borderRadius: "16px",
  boxShadow: "0 10px 25px -5px rgba(0, 0, 0, 0.05)",
  textAlign: "center",
};

const logoWrapperStyle = {
  display: "flex",
  justifyContent: "center",
  marginBottom: "16px",
};

const logoCircleStyle = {
  width: "60px",
  height: "60px",
  borderRadius: "50%",
  backgroundColor: "#ffedd5",
  display: "flex",
  alignItems: "center",
  justifyContent: "center",
};

const titleStyle = {
  fontSize: "22px",
  fontWeight: "700",
  color: "#1e293b",
  marginBottom: "6px",
};

const subtitleStyle = {
  fontSize: "14px",
  color: "#64748b",
  marginBottom: "24px",
};

const inputGroupStyle = {
  marginBottom: "18px",
  textAlign: "left",
};

const labelStyle = {
  display: "block",
  fontSize: "13px",
  fontWeight: "600",
  color: "#475569",
  marginBottom: "6px",
};

const inputStyle = {
  width: "100%",
  padding: "12px 14px",
  borderRadius: "8px",
  border: "1px solid #cbd5e1",
  fontSize: "14px",
  outline: "none",
  boxSizing: "border-box",
};

const togglePasswordStyle = {
  position: "absolute",
  right: "10px",
  top: "50%",
  transform: "translateY(-50%)",
  background: "none",
  border: "none",
  cursor: "pointer",
  fontSize: "16px",
};

const buttonStyle = {
  width: "100%",
  padding: "12px",
  backgroundColor: "#2563eb",
  color: "#ffffff",
  border: "none",
  borderRadius: "8px",
  fontSize: "15px",
  fontWeight: "600",
  cursor: "pointer",
  marginTop: "10px",
};

const errorStyle = {
  marginBottom: "16px",
  padding: "10px 14px",
  borderRadius: "8px",
  backgroundColor: "#fef2f2",
  color: "#991b1b",
  fontSize: "13px",
  textAlign: "left",
  border: "1px solid #fecaca",
};

export default Login;
