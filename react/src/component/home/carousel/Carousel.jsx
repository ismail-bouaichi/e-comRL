import React, { useRef } from 'react';
import { Swiper } from 'swiper/react';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const CarouselNavigation = ({ 
  buttonPosition = 'outside',
  buttonClassName = '',
  prevActivateId,
  nextActivateId
}) => {
  return (
    <>
      <button
        id={prevActivateId || 'prev'}
        className={`swiper-button-prev w-12 h-12 rounded-full bg-white shadow-md flex items-center justify-center absolute z-10 transition-all hover:bg-gray-50 ${
          buttonPosition === 'inside' ? 'left-4' : '-left-6'
        } ${buttonClassName}`}
      >
        <span className="sr-only">Previous</span>
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <button
        id={nextActivateId || 'next'}
        className={`swiper-button-next w-12 h-12 rounded-full bg-white shadow-md flex items-center justify-center absolute z-10 transition-all hover:bg-gray-50 ${
          buttonPosition === 'inside' ? 'right-4' : '-right-6'
        } ${buttonClassName}`}
      >
        <span className="sr-only">Next</span>
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </>
  );
};

const Carousel = ({
  children,
  className = '',
  prevActivateId = '',
  nextActivateId = '',
  buttonClassName = '',
  buttonPosition = 'outside',
  breakpoints,
  loop = true,
  autoplay = false,
  showNavigation = true,
  ...props
}) => {
  const prevRef = useRef(null);
  const nextRef = useRef(null);

  return (
    <div className={`carouselWrapper relative ${className}`}>
      <Swiper
        modules={[Navigation, Pagination, Autoplay]}
        loop={loop}
        autoplay={autoplay}
        breakpoints={breakpoints}
        navigation={{
          prevEl: prevActivateId ? `#${prevActivateId}` : prevRef.current,
          nextEl: nextActivateId ? `#${nextActivateId}` : nextRef.current,
        }}
        {...props}
      >
        {children}
      </Swiper>
      {showNavigation && (
        <CarouselNavigation
          buttonPosition={buttonPosition}
          buttonClassName={buttonClassName}
          prevActivateId={prevActivateId}
          nextActivateId={nextActivateId}
        />
      )}
    </div>
  );
};

export default Carousel;