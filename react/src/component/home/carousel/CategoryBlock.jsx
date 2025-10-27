import React from 'react';
import { SwiperSlide } from 'swiper/react';
import Carousel from './Carousel';
import { useNavigate } from 'react-router-dom'; // Add this import


const breakpoints = {
  1720: {
    slidesPerView: 8,
    spaceBetween: 28,
  },
  1400: {
    slidesPerView: 7,
    spaceBetween: 28,
  },
  1025: {
    slidesPerView: 6,
    spaceBetween: 28,
  },
  768: {
    slidesPerView: 5,
    spaceBetween: 20,
  },
  500: {
    slidesPerView: 4,
    spaceBetween: 20,
  },
  0: {
    slidesPerView: 3,
    spaceBetween: 12,
  },
};

const CategoryBlock = ({
  className = 'mb-10 md:mb-11 lg:mb-12 xl:mb-14 lg:pb-1 xl:pb-0',
  categories,
  loading,
  error,
}) => {

    const navigate = useNavigate(); // Add this hook

    const handleCategoryClick = (category) => {
      navigate('/store', { state: { selectedCategory: category.name } });
    };
  if (!loading && (!categories || categories.length === 0)) {
    return <div>No categories found</div>;
  }

  

  return (
    <div className={className}>
      <h2 className="text-2xl font-bold mb-8">Shop By Category</h2>
      {error ? (
        <div className="text-red-500">{error}</div>
      ) : (
        <Carousel
          breakpoints={breakpoints}
          buttonClassName="-mt-8 md:-mt-10"
          autoplay={{ delay: 3500 }}
          loop={true}
          prevActivateId="categoriesSlidePrev"
          nextActivateId="categoriesSlideNext"
        >
          {loading
            ? Array.from({ length: 10 }).map((_, idx) => (
                <SwiperSlide key={`loading-${idx}`}>
                  <div className="animate-pulse">
                    <div className="bg-gray-200 h-48 rounded-lg"></div>
                    <div className="h-4 bg-gray-200 rounded mt-4 w-3/4 mx-auto"></div>
                  </div>
                </SwiperSlide>
              ))
            : categories.map((category) => (
                <SwiperSlide key={category.id}>
                   <div 
                    className="cursor-pointer group"
                    onClick={() => handleCategoryClick(category)}
                  >
                    <div className={`
                      aspect-square rounded-lg overflow-hidden
                      ${category.color || 'bg-blue-500'}
                      flex items-center justify-center
                      transition-transform group-hover:scale-95
                    `}>
                      <h3 className="text-3xl text-white font-light tracking-wide
                                   transition-all group-hover:scale-110
                                   text-center px-4"
                          style={{
                            textShadow: '0 0 15px rgba(255,255,255,0.8)'
                          }}>
                        {category.name}
                      </h3>
                    </div>
                    <div className="text-center mt-4">
                      <p className="text-sm text-gray-600">
                        {category.productCount} Products
                      </p>
                    </div>
                  </div>
                </SwiperSlide>
              ))}
        </Carousel>
      )}
    </div>
  );
};

export default CategoryBlock;