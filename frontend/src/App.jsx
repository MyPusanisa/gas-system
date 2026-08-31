import { useState } from "react"
import { Routes, Route } from "react-router-dom"
import Login from "./pages/Login"
import Dashboard from "./pages/Dashboard"
import GasPage from "./pages/GasPage"
import DeliveryPage from "./pages/DeliveryPage"
import QRCodePage from "./pages/QRCodePage"
import MaintenancePage from "./pages/MaintenancePage"
import CylinderPage from "./pages/CylinderPage"
import StaffPage from "./pages/StaffPage"
import ProtectedRoute from "./components/ProtectedRoute"

function App() {
  // สร้าง State สำหรับเก็บข้อมูลและส่งต่อไปยังแต่ละหน้า
  const [customers, setCustomers] = useState([]);
  const [staffs, setStaffs] = useState([])
  const [cylinders, setCylinders] = useState([])
  const [deliveries, setDeliveries] = useState([])
  const [maintenances, setMaintenances] = useState([])

  return (
    <Routes>
      <Route path="/" element={<Login />} />

      <Route
        path="/dashboard"
        element={
          <ProtectedRoute allowedRoles={["admin"]}>
            <Dashboard cylinders={cylinders} deliveries={deliveries} />
          </ProtectedRoute>
        }
      />

      {/* หน้ารายชื่อพนักงานส่ง (คุมสิทธิ์เฉพาะ admin) */}
      <Route
        path="/staff"
        element={
          <ProtectedRoute allowedRoles={["admin"]}>
            <StaffPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/gas"
        element={
          <ProtectedRoute allowedRoles={["admin"]}>
            <GasPage
              cylinders={cylinders}
              setCylinders={setCylinders}
              deliveries={deliveries}
            />
          </ProtectedRoute>
        }
      />

      <Route
        path="/delivery"
        element={
          <ProtectedRoute allowedRoles={["admin", "staff"]}>
            <DeliveryPage
              deliveries={deliveries}
              setDeliveries={setDeliveries}
              cylinders={cylinders}
              setCylinders={setCylinders}
              staffs={staffs}
              setStaffs={setStaffs}
              customers={customers}
              setCustomers={setCustomers}
            />
          </ProtectedRoute>
        }
      />

      <Route
        path="/maintenance"
        element={
          <ProtectedRoute allowedRoles={["admin"]}>
            <MaintenancePage
              cylinders={cylinders}
              setCylinders={setCylinders}
              maintenances={maintenances}
              setMaintenances={setMaintenances}
            />
          </ProtectedRoute>
        }
      />

      {/* หน้าสำหรับฝั่งลูกค้าสแกนตรวจสอบข้อมูล */}
      <Route path="/cylinder/:id" element={<QRCodePage cylinders={cylinders} />} />

    </Routes>
  )
}

export default App