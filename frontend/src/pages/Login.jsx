import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { fetchAPI } from "../services/api";
import { Flame, Eye, EyeOff, User, Lock, TriangleAlert } from "lucide-react";

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
        <div style={logoCircleStyle}>
          <Flame size={28} />
        </div>

        <h2 style={titleStyle}>GAS SYS</h2>
        <p style={subtitleStyle}>ระบบจัดการคลังและการจัดส่งถังแก๊ส</p>

        <form onSubmit={handleLogin}>
          <div style={inputGroupStyle}>
            <label style={labelStyle}>ชื่อผู้ใช้งาน</label>
            <div style={inputWrapStyle}>
              <User size={16} style={inputIconStyle} />
              <input
                type="text"
                placeholder="Username"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                style={inputStyle}
                disabled={loading}
                autoComplete="username"
                autoFocus
              />
            </div>
          </div>

          <div style={inputGroupStyle}>
            <label style={labelStyle}>รหัสผ่าน</label>
            <div style={inputWrapStyle}>
              <Lock size={16} style={inputIconStyle} />
              <input
                type={showPassword ? "text" : "password"}
                placeholder="Password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                style={{ ...inputStyle, paddingRight: "44px" }}
                disabled={loading}
                autoComplete="current-password"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                style={togglePasswordStyle}
                title={showPassword ? "ซ่อนรหัสผ่าน" : "แสดงรหัสผ่าน"}
              >
                {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
              </button>
            </div>
          </div>

          {error && (
            <div style={errorStyle}>
              <TriangleAlert size={16} style={{ flexShrink: 0, marginTop: "2px" }} />
              <span>{error}</span>
            </div>
          )}

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
  padding: "16px",
  boxSizing: "border-box",
  background: "radial-gradient(circle at 20% 0%, rgba(59,130,246,0.18), transparent 45%), radial-gradient(circle at 90% 100%, rgba(249,115,22,0.14), transparent 45%), #0f172a",
};

const cardStyle = {
  width: "100%",
  maxWidth: "400px",
  padding: "36px 32px",
  background: "rgba(31,41,55,0.9)",
  border: "1px solid #374151",
  borderRadius: "18px",
  boxShadow: "0 20px 50px rgba(0,0,0,0.45)",
  textAlign: "center",
  boxSizing: "border-box",
};

const logoCircleStyle = {
  width: "60px",
  height: "60px",
  margin: "0 auto 16px",
  borderRadius: "16px",
  background: "linear-gradient(135deg, #f97316, #ef4444)",
  display: "flex",
  alignItems: "center",
  justifyContent: "center",
  color: "white",
  boxShadow: "0 8px 20px rgba(239,68,68,0.35)",
};

const titleStyle = { fontSize: "24px", fontWeight: 700, margin: "0 0 6px", color: "white" };
const subtitleStyle = { fontSize: "14px", color: "#9ca3af", margin: "0 0 28px" };

const inputGroupStyle = { marginBottom: "16px", textAlign: "left" };
const labelStyle = { display: "block", fontSize: "13px", fontWeight: 600, color: "#cbd5e1", marginBottom: "6px" };
const inputWrapStyle = { position: "relative" };
const inputIconStyle = { position: "absolute", left: "14px", top: "50%", transform: "translateY(-50%)", color: "#6b7280", pointerEvents: "none" };

const inputStyle = {
  width: "100%",
  height: "46px",
  padding: "0 14px 0 40px",
  borderRadius: "10px",
  border: "1px solid #374151",
  background: "#111827",
  color: "white",
  fontSize: "15px",
  boxSizing: "border-box",
};

const togglePasswordStyle = {
  position: "absolute",
  right: "8px",
  top: "50%",
  transform: "translateY(-50%)",
  display: "flex",
  padding: "6px",
  background: "none",
  border: "none",
  color: "#9ca3af",
  cursor: "pointer",
};

const buttonStyle = {
  width: "100%",
  height: "46px",
  marginTop: "8px",
  background: "#2563eb",
  color: "white",
  border: "none",
  borderRadius: "10px",
  fontSize: "15px",
  fontWeight: 600,
  cursor: "pointer",
};

const errorStyle = {
  display: "flex",
  gap: "8px",
  marginBottom: "14px",
  padding: "10px 14px",
  borderRadius: "10px",
  background: "rgba(239,68,68,0.12)",
  border: "1px solid rgba(239,68,68,0.5)",
  color: "#fca5a5",
  fontSize: "13px",
  textAlign: "left",
};

export default Login;
