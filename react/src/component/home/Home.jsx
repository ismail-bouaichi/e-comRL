
import Swipe from './Swipe'
import FlashSale from './FlashSale'
import { Hero } from './Hero'
import Brand from './Brand'
import {  useSelector } from 'react-redux';
import CategoryBlock from './carousel/CategoryBlock';

const Home = () => {

 


  

  const categories = useSelector((state) => state.category.categories);
  const loading = useSelector((state) => state.category.loading);
  const error = useSelector((state) => state.category.error);
  const brands = useSelector((state) => state.brand.brands);

 

  return (
    <main className='relative flex-grow '>
     <Hero/>

    <div className='mx-auto max-w-[1920px] px-4 md:px-8 2xl:px-16'>
      <FlashSale/>
    </div>

    <div className='mx-auto max-w-[1920px] px-4 md:px-8 2xl:px-16'>
        <CategoryBlock 
          categories={categories}
          loading={loading}
          error={error}
        />
      </div>

    <div className='mx-auto max-w-[1920px] px-4 md:px-8 2xl:px-16'>
      <Brand brands={brands}/>
    </div>

    </main>
  )
}

export default Home
