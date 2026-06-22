'use client';

import { useRecommendationStore } from '@/store/useRecommendationStore';
import { formatVND } from '@/lib/constants';
import { Wallet } from 'lucide-react';

const MIN_PRICE = 10_000;
const MAX_PRICE = 2_000_000;
const STEP = 10_000;

export default function BudgetSlider() {
  const { priceMax, setPriceMax } = useRecommendationStore();

  return (
    <div className="space-y-2">
      <label className="block text-sm font-semibold text-gray-700">
        💰 Ngân sách tối đa / người
      </label>

      <div className="flex items-center gap-3">
        <Wallet className="w-5 h-5 text-primary-500" />
        <input
          type="range"
          min={MIN_PRICE}
          max={MAX_PRICE}
          step={STEP}
          value={priceMax}
          onChange={(e) => setPriceMax(parseInt(e.target.value))}
          className="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer
                     accent-primary-500"
        />
      </div>

      <div className="flex justify-between text-xs text-gray-400">
        <span>{formatVND(MIN_PRICE)}</span>
        <span>{formatVND(MAX_PRICE)}</span>
      </div>

      <p className="text-lg font-bold text-primary-600 text-center">
        {formatVND(priceMax)}
      </p>
    </div>
  );
}
