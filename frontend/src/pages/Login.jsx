import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { fetchAPI } from "../services/api";

function Login() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const navigate = useNavigate();

  const handleLogin = async () => {
    if (!username || !password) {
      setError("กรุณากรอก Username และ Password ให้ครบถ้วน");
      return;
    }

    setError("");
    const data = await fetchAPI("/login.php", {
      method: "POST",
      body: JSON.stringify({ username, password }),
    });

    if (data.success) {
      localStorage.setItem("isLoggedIn", "true");
      localStorage.setItem("role", data.role);
      localStorage.setItem("name", data.name);
      localStorage.setItem("admin_id", data.admin_id);
      localStorage.setItem("username", data.username);
      localStorage.setItem("staff_id", data.staff_id);

      if (data.role === "admin") {
        navigate("/dashboard");
      } else {
        navigate("/delivery");
      }
    } else {
      setError(data.message || "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง");
    }
  };

  return (
    <div style={{ padding: "40px", maxWidth: "400px", margin: "60px auto" }}>
      <h2 style={{ marginBottom: "20px" }}>เข้าสู่ระบบ</h2>

      <div style={{ marginBottom: "15px" }}>
        <label>Username</label>
        <input
          type="text"
          placeholder="admin หรือ staff"
          value={username}
          onChange={(e) => setUsername(e.target.value)}
          style={inputStyle}
        />
      </div>

      <div style={{ marginBottom: "15px" }}>
        <label>Password</label>
        <input
          type="password"
          placeholder="รหัสผ่าน"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          style={inputStyle}
        />
      </div>

      {error && <div style={errorStyle}>{error}</div>}

      <button onClick={handleLogin} style={buttonStyle}>
        Login
      </button>
    </div>
  );
}

const inputStyle = {
  width: "100%",
  padding: "10px",
  marginTop: "6px",
  borderRadius: "8px",
  border: "1px solid #ccc",
  boxSizing: "border-box",
};

const buttonStyle = {
  width: "100%",
  padding: "12px",
  backgroundColor: "#2563eb",
  color: "white",
  border: "none",
  borderRadius: "8px",
  cursor: "pointer",
};

const errorStyle = {
  marginBottom: "15px",
  padding: "10px",
  borderRadius: "8px",
  background: "#fee2e2",
  color: "#b91c1c",
};

export default Login;