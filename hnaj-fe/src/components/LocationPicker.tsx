'use client';

import { useRecommendationStore } from '@/store/useRecommendationStore';
import { MapPin, Navigation, Loader2 } from 'lucide-react';

export default function LocationPicker() {
  const { location, locationAddress, setLocation } = useRecommendationStore();

  const handleGetLocation = () => {
    if (!navigator.geolocation) {
      alert('Trình duyệt của bạn không hỗ trợ định vị. Vui lòng nhập thủ công.');
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        setLocation(
          position.coords.latitude,
          position.coords.longitude
        );
      },
      (error) => {
        console.error('Geolocation error:', error);
        alert('Không thể lấy vị trí. Vui lòng kiểm tra quyền định vị và thử lại.');
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
    );
  };

  return (
    <div className="space-y-2">
      <label className="block text-sm font-semibold text-gray-700">
        📍 Vị trí của bạn
      </label>

      <button
        type="button"
        onClick={handleGetLocation}
        className="w-full flex items-center justify-center gap-2 px-4 py-3
                   bg-gradient-to-r from-primary-500 to-primary-600
                   text-white font-medium rounded-xl
                   hover:from-primary-600 hover:to-primary-700
                   active:scale-[0.98] transition-all duration-200
                   shadow-lg shadow-primary-200"
      >
        <Navigation className="w-5 h-5" />
        {location ? '📍 Đã định vị' : '🎯 Lấy vị trí hiện tại'}
      </button>

      {location && (
        <div className="flex items-center gap-2 px-3 py-2 bg-green-50 border border-green-200 rounded-lg animate-fade-up">
          <MapPin className="w-4 h-4 text-green-600 flex-shrink-0" />
          <span className="text-sm text-green-700 truncate">
            {locationAddress || `${location.lat.toFixed(4)}, ${location.lng.toFixed(4)}`}
          </span>
        </div>
      )}
    </div>
  );
}
