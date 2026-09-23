const API_BASE_URL = '/Backend/models';

export const fetchAPI = async (endpoint, options = {}) => {
  const config = {
    headers: {
      "Content-Type": "application/json",
      ...options.headers,
    },
    credentials: "include",
    ...options,
  };

  try {
    // จัดการลบ /Backend หรือ /models ที่อาจจะซ้ำซ้อนจาก endpoint
    let cleanEndpoint = endpoint.replace(/^\/?(Backend\/)?(models\/)?/, '');
    const targetUrl = `${API_BASE_URL}/${cleanEndpoint}`;

    const response = await fetch(targetUrl, config);
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || `HTTP error! status: ${response.status}`);
    }

    return data;
  } catch (error) {
    console.error(`[API Error] ${endpoint}:`, error);
    return {
      success: false,
      message: error.message || "ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้",
    };
  }
};