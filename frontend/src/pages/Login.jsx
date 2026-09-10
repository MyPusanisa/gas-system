import React, { useState } from 'react';

const Login = () => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async (e) => {
    e.preventDefault();
    setErrorMessage('');
    setLoading(true);

    try {
      const response = await fetch('http://localhost:8080/Backend/models/login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          username: username.trim(),
          password: password.trim(),
        }),
      });

      const result = await response.json();

      if (result && result.success) {
        // ดึงชื่อพนักงาน/แอดมินให้ชัวร์
        const displayName = result.name || result.staff_name || result.username;

        // บันทึกลง localStorage ทั้งรูปแบบ Object และ String คีย์เดี่ยว
        const userData = {
          ...result,
          name: displayName,
          staff_name: displayName
        };

        localStorage.setItem('user', JSON.stringify(userData));
        localStorage.setItem('userName', displayName);
        localStorage.setItem('name', displayName);
        localStorage.setItem('role', result.role);
        localStorage.setItem('isLoggedIn', 'true');
        
        // สลับไปหน้าตาม Role
        if (result.role === 'admin') {
          window.location.href = '/staff';
        } else {
          window.location.href = '/delivery';
        }
      } else {
        setErrorMessage(result.message || 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง');
      }
    } catch (error) {
      console.error('Login Error:', error);
      setErrorMessage('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-container">
      <form onSubmit={handleLogin} className="login-form">
        <h2>เข้าสู่ระบบ</h2>

        {errorMessage && (
          <div className="error-banner" style={{ color: 'red', marginBottom: '1rem' }}>
            {errorMessage}
          </div>
        )}

        <div className="form-group">
          <label htmlFor="username">Username</label>
          <input
            id="username"
            type="text"
            value={username}
            onChange={(e) => setUsername(e.target.value)}
            placeholder="ป้อน Username"
            required
          />
        </div>

        <div className="form-group">
          <label htmlFor="password">Password</label>
          <input
            id="password"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="ป้อน Password"
            required
          />
        </div>

        <button type="submit" disabled={loading}>
          {loading ? 'กำลังเข้าสู่ระบบ...' : 'Login'}
        </button>
      </form>
    </div>
  );
};

export default Login;