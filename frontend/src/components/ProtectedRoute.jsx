import { Navigate } from "react-router-dom"

function ProtectedRoute({ children, allowedRoles }) {
  const isLoggedIn = localStorage.getItem("isLoggedIn") === "true"
  const role = localStorage.getItem("role")

  if (!isLoggedIn) {
    return <Navigate to="/" replace />
  }

  if (allowedRoles && !allowedRoles.includes(role)) {
    return <Navigate to="/dashboard" replace />
  }

  return children
}

export default ProtectedRoute