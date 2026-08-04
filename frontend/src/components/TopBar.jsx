import { useState, useEffect } from "react";

function TopBar({
  role,
  gasLevel,
  maintenanceDueItems = [],
  expiredCylinderItems = [],
  deliverySuccessItems = [],
  newAssignedJobItems = [],
}) {
  const [openPanel, setOpenPanel] = useState("");
  const [newJobsCount, setNewJobsCount] = useState(0);
  const loginName = localStorage.getItem("name");

  const getGasColor = () => {
    if (gasLevel <= 520) return "#22c55e";
    if (gasLevel <= 720) return "#facc15";
    return "#ef4444";
  };

  const togglePanel = (panelName) => {
    setOpenPanel((prev) => (prev === panelName ? "" : panelName));
  };

  const maintenanceCount = maintenanceDueItems.length;
  const expiredCount = expiredCylinderItems.length;
  const successCount = deliverySuccessItems.length;

  const fetchNewJobsCount = async () => {
    try {
      const apiUrl = process.env.REACT_APP_API_BASE_URL
        ? `${process.env.REACT_APP_API_BASE_URL}/models/delivery/get_new_jobs_count.php`
        : "/Backend/models/delivery/get_new_jobs_count.php";

      const response = await fetch(apiUrl, {
        method: "GET",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data = await response.json();
      if (data.count !== undefined) setNewJobsCount(data.count);
      else console.error("Invalid response:", data);
    } catch (error) {
      console.error("Error fetching new jobs count:", error);
      setNewJobsCount(0);
    }
  };

  useEffect(() => {
    if (role === "staff") {
      fetchNewJobsCount();
      const interval = setInterval(fetchNewJobsCount, 30000);
      return () => clearInterval(interval);
    }
  }, [role]);

  return (
    <div style={wrapperStyle}>
      <div style={topBarStyle}>
        <div style={leftSectionStyle}>
          {role === "admin" && (
            <button onClick={() => togglePanel("gas")} style={topButtonStyle} type="button">
              <span style={{ ...statusDotStyle, backgroundColor: getGasColor() }} />
              <span>แก๊สในคลัง</span>
            </button>
          )}
        </div>

        <div style={rightSectionStyle}>
          {role === "admin" && (
            <>
              <button onClick={() => togglePanel("maintenance")} style={topButtonStyle} type="button">
                ตรวจสภาพ {maintenanceCount}
              </button>
              <button onClick={() => togglePanel("expired")} style={topButtonStyle} type="button">
                หมดอายุ {expiredCount}
              </button>
              <button onClick={() => togglePanel("success")} style={topButtonStyle} type="button">
                ส่งสำเร็จ {successCount}
              </button>
            </>
          )}

          {role === "staff" && (
            <button onClick={() => togglePanel("newJobs")} style={topButtonStyle} type="button">
              งานเข้า {newJobsCount}
            </button>
          )}

          <div style={userBoxStyle}>
            <span style={userLabelStyle}>ผู้ใช้:</span>
            <span style={userNameStyle}>{loginName || "-"}</span>
          </div>
        </div>
      </div>

      {openPanel && (
        <div style={panelStyle}>
          {openPanel === "gas" && (
            <div>
              <h3 style={panelTitleStyle}>สถานะแก๊สในคลัง</h3>
              <p style={panelTextStyle}>ค่าปัจจุบัน: {gasLevel}</p>
              <p style={panelTextStyle}>
                สถานะ: {gasLevel <= 520 ? "ปกติ" : gasLevel <= 720 ? "เฝ้าระวัง" : "อันตราย"}
              </p>
            </div>
          )}

          {openPanel === "maintenance" && (
            <div>
              <h3 style={panelTitleStyle}>ถังที่ถึงวันตรวจสภาพ</h3>
              {maintenanceCount > 0 ? (
                maintenanceDueItems.map((item) => (
                  <div key={item.id} style={listItemStyle}>
                    {item.id} - {item.brand || "-"} - {item.size} - {item.nextMaintenanceDate}
                  </div>
                ))
              ) : (
                <p style={panelTextStyle}>ไม่มีรายการ</p>
              )}
            </div>
          )}

          {openPanel === "expired" && (
            <div>
              <h3 style={panelTitleStyle}>ถังที่หมดอายุ</h3>
              {expiredCount > 0 ? (
                expiredCylinderItems.map((item) => (
                  <div key={item.id} style={listItemStyle}>
                    {item.id} - {item.brand || "-"} - {item.size}
                  </div>
                ))
              ) : (
                <p style={panelTextStyle}>ไม่มีรายการ</p>
              )}
            </div>
          )}

          {openPanel === "success" && (
            <div>
              <h3 style={panelTitleStyle}>ออเดอร์ที่ส่งสำเร็จแล้ว</h3>
              {successCount > 0 ? (
                deliverySuccessItems.map((item) => (
                  <div key={item.id} style={listItemStyle}>
                    {item.id} - {item.customerName || item.customer || "-"}
                  </div>
                ))
              ) : (
                <p style={panelTextStyle}>ไม่มีรายการ</p>
              )}
            </div>
          )}

          {openPanel === "newJobs" && (
            <div>
              <h3 style={panelTitleStyle}>งานเข้าใหม่</h3>
              {newJobsCount > 0 ? (
                newAssignedJobItems.map((item) => (
                  <div key={item.id} style={listItemStyle}>
                    {item.id} - {item.customerName || item.customer || "-"}
                  </div>
                ))
              ) : (
                <p style={panelTextStyle}>ไม่มีรายการ</p>
              )}
            </div>
          )}
        </div>
      )}
    </div>
  );
}

// Style Objects
const wrapperStyle = {
  width: "100%",
  background: "#0f172a",
  borderBottom: "1px solid #1f2937",
  boxSizing: "border-box",
  position: "relative",
  zIndex: 1,
};

const topBarStyle = {
  padding: "12px 20px",
  display: "flex",
  justifyContent: "space-between",
  alignItems: "center",
  gap: "20px",
  flexWrap: "wrap",
};

const leftSectionStyle = {
  display: "flex",
  alignItems: "center",
  gap: "12px",
};

const rightSectionStyle = {
  display: "flex",
  alignItems: "center",
  gap: "12px",
  flexWrap: "wrap",
  marginLeft: "auto",
};

const topButtonStyle = {
  display: "flex",
  alignItems: "center",
  gap: "8px",
  background: "#111827",
  color: "white",
  border: "1px solid #374151",
  borderRadius: "10px",
  padding: "8px 12px",
  cursor: "pointer",
};

const statusDotStyle = {
  width: "10px",
  height: "10px",
  borderRadius: "50%",
  flexShrink: 0,
};

const userBoxStyle = {
  display: "flex",
  alignItems: "center",
  gap: "6px",
  background: "#111827",
  borderRadius: "10px",
  padding: "8px 12px",
};

const userLabelStyle = {
  color: "#9ca3af",
  fontSize: "13px",
};

const userNameStyle = {
  color: "white",
  fontSize: "13px",
  fontWeight: "bold",
};

const panelStyle = {
  background: "#111827",
  color: "white",
  padding: "16px 20px",
  borderTop: "1px solid #1f2937",
  position: "relative",
  zIndex: 1,
};

const panelTitleStyle = {
  marginTop: 0,
  marginBottom: "12px",
};

const panelTextStyle = {
  margin: "6px 0",
};

const listItemStyle = {
  padding: "8px 0",
  borderBottom: "1px solid #374151",
};

export default TopBar;