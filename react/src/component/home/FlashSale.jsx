import React, { useEffect } from 'react';
import { useSelector, useDispatch } from 'react-redux';
import { Link } from 'react-router-dom';
import { fetchBestSellingProducts } from '../../redux/actions/productActions';
import { 
  selectBestSellingProducts, 
  selectBestSellingLoading, 
   selectBestSellingError
} from '../../redux/selectors/productSelectors';

const ProductCard = ({ product, user }) => {
  const imagePath = product.image?.file_path || 'default-placeholder.jpg';
  const hasDiscount = product.is_discounted && product.discounted_price < product.price;

  return (
    <div className="group box-border overflow-hidden flex rounded-md cursor-pointer flex-col items-start bg-white hover:shadow-lg transition-shadow duration-300">
      <Link 
        className="flex relative mb-3 md:mb-3.5 w-full aspect-square rounded-md overflow-hidden"
        to={`/product/${product.id}`}
        state={{ user }}
      >
        <img 
          src={`http://127.0.0.1:8000/storage/${imagePath}`}
          alt={product.name}
          className="bg-gray-300 object-cover transition duration-300 ease-linear transform group-hover:scale-105"
          style={{
            position: 'absolute',
            height: '100%',
            width: '100%',
            left: '0',
            top: '0',
            right: '0',
            bottom: '0',
            color: 'transparent'
          }}
        />
      </Link>
      
      <div className="w-full px-4 pb-4">
        <h2 className="text-heading font-semibold truncate mb-1 md:mb-1.5 text-sm sm:text-base">
          {product.name}
        </h2>
        
        <div className="flex items-center mt-2">
          {hasDiscount ? (
            <>
              <span className="text-heading font-bold text-sm md:text-base">
                ${product.discounted_price}
              </span>
              <span className="text-gray-400 line-through text-sm ml-2">
                ${product.price}
              </span>
              <span className="bg-red-500 text-white text-xs px-2 py-1 rounded ml-2">
                {Math.round((1 - product.discounted_price / product.price) * 100)}% OFF
              </span>
            </>
          ) : (
            <span className="text-heading font-bold text-sm md:text-base">
              ${product.price}
            </span>
          )}
        </div>

        {product.total_sold && (
          <div className="mt-2 text-sm text-gray-500">
            {product.total_sold} sold
          </div>
        )}
      </div>
    </div>
  );
};

const FlashSale = () => {
  const dispatch = useDispatch();
  const products = useSelector(selectBestSellingProducts);
  const loading = useSelector(selectBestSellingLoading);
  const error = useSelector(selectBestSellingError);
  const user = useSelector((state) => state.user.user);

  useEffect(() => {
    dispatch(fetchBestSellingProducts());
  }, [dispatch]);

  if (loading) {
    return (
      <div className="mb-12 md:mb-14 xl:mb-16 border border-gray-300 rounded-md p-5 md:p-6 lg:p-7">
        <div className="animate-pulse grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5 gap-4">
          {[...Array(10)].map((_, i) => (
            <div key={i} className="bg-gray-200 rounded-md aspect-square"></div>
          ))}
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="mb-12 md:mb-14 xl:mb-16 border border-red-300 rounded-md p-5 text-red-500">
        Error loading products: {error}
      </div>
    );
  }

  if (!products?.length) {
    return (
      <div className="mb-12 md:mb-14 xl:mb-16 border border-gray-300 rounded-md p-5 text-gray-500">
        No products available.
      </div>
    );
  }

  return (
    <div className="mb-12 md:mb-14 xl:mb-16 border border-gray-300 rounded-md pt-5 md:pt-6 lg:pt-7 pb-5 lg:pb-7 px-4 md:px-5 lg:px-7">
      <div className="flex flex-wrap items-center justify-between mb-5 md:mb-6">
        <div className="flex items-center justify-between -mt-2 lg:-mt-2.5 mb-0">
          <h3 className="text-heading text-lg md:text-xl lg:text-2xl 2xl:text-3xl xl:leading-10 font-bold">
            Flash Sale
          </h3>
        </div>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5 gap-x-3 md:gap-x-5 xl:gap-x-7 gap-y-4 lg:gap-y-5 xl:lg:gap-y-6 2xl:gap-y-8">
        {products.map((product) => (
          <ProductCard key={product.id} product={product} user={user} />
        ))}
      </div>
    </div>
  );
};

export default FlashSale;