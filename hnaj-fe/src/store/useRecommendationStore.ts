'use client';

import { create } from 'zustand';
import { PlaceResult, ResultMeta } from '@/types';
import { apiClient } from '@/lib/api';

// ============================================================
// Recommendation Store (Zustand)
// ============================================================

interface RecommendationState {
  // --- Input State ---
  location: { lat: number; lng: number } | null;
  locationAddress: string;
  radiusKm: number;
  priceMax: number;
  selectedTags: string[];
  resultMode: 'single' | 'top3';

  // --- Output State ---
  isLoading: boolean;
  results: PlaceResult[];
  meta: ResultMeta | null;
  error: string | null;
  showEmptyState: boolean;

  // --- Actions ---
  setLocation: (lat: number, lng: number, address?: string) => void;
  setRadius: (km: number) => void;
  setPriceMax: (price: number) => void;
  toggleTag: (tagSlug: string) => void;
  setResultMode: (mode: 'single' | 'top3') => void;
  fetchRecommendations: () => Promise<void>;
  reset: () => void;
  dismissError: () => void;
}

const initialState = {
  location: null,
  locationAddress: '',
  radiusKm: 3.0,
  priceMax: 150_000,
  selectedTags: [] as string[],
  resultMode: 'top3' as const,
  isLoading: false,
  results: [] as PlaceResult[],
  meta: null,
  error: null,
  showEmptyState: false,
};

export const useRecommendationStore = create<RecommendationState>((set, get) => ({
  ...initialState,

  setLocation: (lat, lng, address) =>
    set({
      location: { lat, lng },
      locationAddress: address || `${lat.toFixed(4)}, ${lng.toFixed(4)}`,
    }),

  setRadius: (km) => set({ radiusKm: km }),

  setPriceMax: (price) => set({ priceMax: price }),

  toggleTag: (tagSlug) => {
    const { selectedTags } = get();
    if (selectedTags.includes(tagSlug)) {
      set({ selectedTags: selectedTags.filter((t) => t !== tagSlug) });
    } else {
      set({ selectedTags: [...selectedTags, tagSlug] });
    }
  },

  setResultMode: (mode) => set({ resultMode: mode }),

  fetchRecommendations: async () => {
    const { location, radiusKm, priceMax, selectedTags, resultMode } = get();

    if (!location) {
      set({ error: 'Vui lòng chọn vị trí hiện tại trước.' });
      return;
    }

    set({ isLoading: true, error: null, results: [], meta: null, showEmptyState: false });

    try {
      const data = await apiClient.getRecommendations({
        location: { lat: location.lat, lng: location.lng },
        radius_km: radiusKm,
        price_max: priceMax,
        tags: selectedTags,
        limit: resultMode === 'single' ? 1 : 3,
      });

      if (data.places.length === 0) {
        set({
          isLoading: false,
          showEmptyState: true,
          meta: data.meta,
          results: [],
        });
      } else {
        set({
          isLoading: false,
          results: data.places,
          meta: data.meta,
          showEmptyState: false,
        });
      }
    } catch (err: any) {
      set({
        isLoading: false,
        error: err.message || 'Có lỗi xảy ra. Vui lòng thử lại.',
        results: [],
        meta: null,
      });
    }
  },

  reset: () => set({ ...initialState }),

  dismissError: () => set({ error: null }),
}));
