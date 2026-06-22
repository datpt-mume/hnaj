'use client';

import { PlaceResult } from '@/types';
import { formatDistance } from '@/lib/constants';
import { MapPin, Star } from 'lucide-react';
import clsx from 'clsx';

interface PlaceCardProps {
  place: PlaceResult;
  index?: number;
}

export default function PlaceCard({ place, index = 0 }: PlaceCardProps) {
  return (
    <div
      className={clsx(
        'bg-white rounded-2xl shadow-lg overflow-hidden',
        'border border-gray-100 hover:shadow-xl',
        'transition-all duration-300 animate-fade-up'
      )}
      style={{ animationDelay: `${index * 150}ms` }}
    >
      {/* Cover Image */}
      <div className="relative h-48 bg-gradient-to-br from-primary-100 to-primary-50 overflow-hidden">
        {place.cover_image ? (
          <img
            src={place.cover_image}
            alt={place.name}
            className="w-full h-full object-cover"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center">
            <span className="text-6xl">📍</span>
          </div>
        )}

        {/* Distance Badge */}
        <div className="absolute top-3 right-3 flex items-center gap-1 px-2.5 py-1
                        bg-white/90 backdrop-blur-sm rounded-full text-xs font-semibold
                        text-gray-700 shadow-md">
          <MapPin className="w-3 h-3 text-primary-500" />
          {formatDistance(place.distance_km)}
        </div>

        {/* Rating Badge */}
        {place.rating > 0 && (
          <div className="absolute top-3 left-3 flex items-center gap-1 px-2.5 py-1
                          bg-white/90 backdrop-blur-sm rounded-full text-xs font-semibold
                          text-yellow-700 shadow-md">
            <Star className="w-3 h-3 fill-yellow-500 text-yellow-500" />
            {place.rating.toFixed(1)}
          </div>
        )}
      </div>

      {/* Info */}
      <div className="p-4 space-y-3">
        <h3 className="text-lg font-bold text-gray-800 line-clamp-1">
          {place.name}
        </h3>

        {place.address && (
          <p className="text-sm text-gray-500 line-clamp-1">{place.address}</p>
        )}

        {/* Price */}
        <div className="flex items-center gap-2">
          <span className="text-sm font-semibold text-primary-600 bg-primary-50 px-2.5 py-1 rounded-full">
            💰 {place.price_display}
          </span>
        </div>

        {/* Tags */}
        {place.tags.length > 0 && (
          <div className="flex flex-wrap gap-1.5">
            {place.tags.map((tag) => {
              const isMatched = place.matched_tags.includes(tag.slug);
              return (
                <span
                  key={tag.id}
                  className={clsx(
                    'px-2 py-0.5 rounded-full text-xs font-medium',
                    isMatched
                      ? 'bg-green-100 text-green-700 border border-green-300'
                      : 'bg-gray-100 text-gray-500'
                  )}
                >
                  {tag.name}
                  {isMatched && ' ✓'}
                </span>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
