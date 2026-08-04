function GasLevelBar({ level }) {
  const getColor = () => {
    if (level <= 520) return "#22c55e"
    if (level <= 720) return "#facc15"
    return "#ef4444"
  }

  const getLabel = () => {
    if (level <= 520) return "ปกติ"
    if (level <= 720) return "เฝ้าระวัง"
    return "อันตราย"
  }

  const color = getColor()

  const barPercent = Math.min((level / 1000) * 100, 100)

  return (
    <div style={wrapperStyle}>
      <div
        style={{
          ...dotStyle,
          backgroundColor: color,
        }}
      />
      <div style={barBackgroundStyle}>
        <div
          style={{
            ...barFillStyle,
            width: `${barPercent}%`,
            backgroundColor: color,
          }}
        />
      </div>
      <span style={textStyle}>
        {level} ({getLabel()})
      </span>
    </div>
  )
}

const wrapperStyle = {
  display: "flex",
  alignItems: "center",
  gap: "10px",
  minWidth: "260px",
}

const dotStyle = {
  width: "12px",
  height: "12px",
  borderRadius: "50%",
  flexShrink: 0,
}

const barBackgroundStyle = {
  width: "120px",
  height: "8px",
  background: "#374151",
  borderRadius: "999px",
  overflow: "hidden",
}

const barFillStyle = {
  height: "100%",
  borderRadius: "999px",
}

const textStyle = {
  fontSize: "13px",
  color: "white",
  whiteSpace: "nowrap",
}

export default GasLevelBar