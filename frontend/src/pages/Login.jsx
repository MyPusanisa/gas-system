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
        const displayName = result.name || result.staff_name || result.username;

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
    <div style={styles.wrapper}>
      <div style={styles.card}>
        <div style={styles.header}>
          <div style={styles.icon}>🔥</div>
          <h2 style={styles.title}>Gas Management System</h2>
          <p style={styles.subtitle}>เข้าสู่ระบบเพื่อจัดการคลังและระบบจัดส่งแก๊ส</p>
        </div>

        {errorMessage && (
          <div style={styles.errorBanner}>
            ⚠️ {errorMessage}
          </div>
        )}

        <form onSubmit={handleLogin} style={styles.form}>
          <div style={styles.formGroup}>
            <label htmlFor="username" style={styles.label}>ชื่อผู้ใช้งาน (Username)</label>
            <input
              id="username"
              type="text"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              placeholder="ระบุ Username"
              required
              style={styles.input}
            />
          </div>

          <div style={styles.formGroup}>
            <label htmlFor="password" style={styles.label}>รหัสผ่าน (Password)</label>
            <input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="ระบุ Password"
              required
              style={styles.input}
            />
          </div>

          <button type="submit" disabled={loading} style={styles.button}>
            {loading ? 'กำลังเข้าสู่ระบบ...' : 'เข้าสู่ระบบ'}
          </button>
        </form>
      </div>
    </div>
  );
};

const styles = {
  wrapper: {
    minHeight: '100vh',
    width: '100vw',
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    background: 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)',
    margin: 0,
    padding: '1rem',
    boxSizing: 'border-box',
    position: 'fixed',
    top: 0,
    left: 0,
    fontFamily: 'sans-serif'
  },
  card: {
    background: '#ffffff',
    width: '100%',
    maxWidth: '400px',
    borderRadius: '16px',
    boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.3)',
    padding: '2.5rem 2rem',
    boxSizing: 'border-box'
  },
  header: {
    textAlign: 'center',
    marginBottom: '1.5rem'
  },
  icon: {
    fontSize: '2.5rem',
    background: '#ffedd5',
    width: '64px',
    height: '64px',
    borderRadius: '50%',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    margin: '0 auto 1rem auto'
  },
  title: {
    color: '#0f172a',
    fontSize: '1.4rem',
    fontWeight: '600',
    margin: '0 0 0.5rem 0'
  },
  subtitle: {
    color: '#64748b',
    fontSize: '0.875rem',
    margin: 0
  },
  errorBanner: {
    backgroundColor: '#fef2f2',
    borderLeft: '4px solid #ef4444',
    color: '#991b1b',
    padding: '0.75rem 1rem',
    borderRadius: '6px',
    fontSize: '0.875rem',
    marginBottom: '1.25rem'
  },
  form: {
    display: 'flex',
    flexDirection: 'column',
    gap: '1.25rem'
  },
  formGroup: {
    display: 'flex',
    flexDirection: 'column',
    gap: '0.4rem',
    textAlign: 'left'
  },
  label: {
    color: '#334155',
    fontSize: '0.875rem',
    fontWeight: '500'
  },
  input: {
    width: '100%',
    padding: '0.75rem 1rem',
    border: '1px solid #cbd5e1',
    borderRadius: '8px',
    fontSize: '1rem',
    outline: 'none',
    boxSizing: 'border-box',
    backgroundColor: '#f8fafc'
  },
  button: {
    width: '100%',
    padding: '0.875rem',
    backgroundColor: '#2563eb',
    color: '#ffffff',
    border: 'none',
    borderRadius: '8px',
    fontSize: '1rem',
    fontWeight: '500',
    cursor: 'pointer',
    marginTop: '0.5rem'
  }
};

export default Login;