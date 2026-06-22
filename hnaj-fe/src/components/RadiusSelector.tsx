'use client';

import { useRecommendationStore } from '@/store/useRecommendationStore';

const RADIUS_OPTIONS = [
  { value: 0.5, label: '500m' },
  { value: 1.0, label: '1km' },
  { value: 2.0, label: '2km' },
  { value: 3.0, label: '3km' },
  { value: 5.0, label: '5km' },
  { value: 10.0, label: '10km' },
  { value: 20.0, label: '20km' },
];

export default function RadiusSelector() {
  const { radiusKm, setRadius } = useRecommendationStore();

  return (
    <div className="space-y-2">
      <label className="block text-sm font-semibold text-gray-700">
        📏 Bán kính tìm kiếm
      </label>

      {/* Slider */}
      <input
        type="range"
        min={0.5}
        max={20}
        step={0.5}
        value={radiusKm}
        onChange={(e) => setRadius(parseFloat(e.target.value))}
        className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer
                   accent-primary-500"
      />

      {/* Preset chips */}
      <div className="flex flex-wrap gap-1.5">
        {RADIUS_OPTIONS.map((opt) => (
          <button
            key={opt.value}
            type="button"
            onClick={() => setRadius(opt.value)}
            className={`px-3 py-1 text-xs font-medium rounded-full transition-all duration-200
              ${radiusKm === opt.value
                ? 'bg-primary-500 text-white shadow-md'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              }`}
          >
            {opt.label}
          </button>
        ))}
      </div>

      <p className="text-sm text-primary-600 font-medium">
        🔍 Trong phạm vi <strong>{radiusKm}km</strong>
      </p>
    </div>
  );
}
