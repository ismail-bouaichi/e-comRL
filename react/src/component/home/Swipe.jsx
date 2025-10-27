import { useNavigate } from 'react-router';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import PropTypes from 'prop-types';

const defaultCategories = [
  {
    name: "Sports",
    icon: "sports",
    productCount: 42,
    color: "bg-blue-500"
  },
  {
    name: "Fashion",
    icon: "fashion",
    productCount: 56,
    color: "bg-green-500"
  },
  {
    name: "Electronics",
    icon: "electronics",
    productCount: 38,
    color: "bg-red-500"
  },
  {
    name: "Books",
    icon: "books",
    productCount: 29,
    color: "bg-purple-500"
  },
  {
    name: "Home",
    icon: "home",
    productCount: 45,
    color: "bg-yellow-500"
  },
  {
    name: "Beauty",
    icon: "beauty",
    productCount: 33,
    color: "bg-pink-500"
  }
];

const Swipe = ({ categories = defaultCategories }) => {
  const navigate = useNavigate();

  const handleCategoryClick = (category) => {
    navigate('/store', { state: { selectedCategory: category.name } });
  };

  const getPlaceholderImage = (category) => {
    // Using a placeholder image service for testing
    return `https://source.unsplash.com/400x300/?${category.icon}`;
    // Alternative placeholder:
    // return `https://via.placeholder.com/400x300/4F46E5/ffffff?text=${category.name}`;
  };

  if (!categories || categories.length === 0) {
    return <p>No categories available</p>;
  }

  return (
    <div className="my-8 px-4">
      <h2 className="text-2xl font-bold mb-6">Shop By Category</h2>
      <Swiper
        modules={[Navigation, Pagination]}
        spaceBetween={20}
        slidesPerView={1}
        navigation
        pagination={{ clickable: true }}
        breakpoints={{
          640: {
            slidesPerView: 2,
          },
          768: {
            slidesPerView: 3,
          },
          1024: {
            slidesPerView: 5,
          },
        }}
        className="w-full"
      >
        {categories.map((item, index) => (
          <SwiperSlide 
            key={index} 
            onClick={() => handleCategoryClick(item)}
            className="cursor-pointer"
          >
            <div className="bg-white rounded-lg overflow-hidden shadow-lg transition-transform hover:scale-105">
              <div className={`relative h-48 ${item.color}`}>
                <img 
                  src={item.icon.startsWith('http') ? item.icon : getPlaceholderImage(item)}
                  className="w-full h-full object-cover mix-blend-overlay"
                  alt={item.name}
                  onError={(e) => {
                    e.target.onerror = null;
                    e.target.src = `https://via.placeholder.com/400x300/${item.color.slice(3)}/ffffff?text=${item.name}`;
                  }}
                />
              </div>
              <div className="p-4 text-center bg-white">
                <h3 className="text-lg font-semibold">{item.name}</h3>
                {item.productCount && (
                  <p className="text-sm text-gray-600 mt-1">
                    {item.productCount} Products
                  </p>
                )}
              </div>
            </div>
          </SwiperSlide>
        ))}
      </Swiper>
    </div>
  );
};

Swipe.propTypes = {
  categories: PropTypes.arrayOf(
    PropTypes.shape({
      name: PropTypes.string.isRequired,
      icon: PropTypes.string.isRequired,
      productCount: PropTypes.number,
      color: PropTypes.string
    })
  )
};

export default Swipe;