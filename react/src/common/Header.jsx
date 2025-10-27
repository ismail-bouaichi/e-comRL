
import  {  useEffect, useContext } from 'react';
import { Routes, Route } from 'react-router-dom';
import Nav from './nav/Nav'
import Home from '../component/home/Home';
import Login from '../component/Login';
import Register from '../component/Register';
import Profile from '../component/profile/Profile';
import Forgot from '../component/Forgot';
import Store from '../component/store/Store';
import Product from '../component/Product';


import { ThemeContext } from '../theme/ThemeContext';
import Success from '../component/Success';
import Confirmed  from '../component/Confirmed';

import { useDispatch, useSelector } from 'react-redux';
import { fetchUserData } from '../redux/actions/userActions';


import { fetchCategories } from '../redux/actions/categoryActions';

import { fetchBrands } from '../redux/actions/brandActions';
import Checkout from '../component/checkout/Checkout';
import ProtectedRoute from '../component/auth/ProtectedRoute';
import { AuthContext } from '../component/auth/AuthProvider';

import LoadingSpinner from '../components/LoadingSpinner';





const Header = ({ openAuthModal }) => {
  const { darkMode } = useContext(ThemeContext);
  const { isAuthenticated, isLoading } = useContext(AuthContext);
  const dispatch = useDispatch();
  const user = useSelector((state) => state.user.user);

  useEffect(() => {
    dispatch(fetchCategories());
    dispatch(fetchBrands());
  }, [dispatch]);

  if (isLoading) {
    return <LoadingSpinner/>; // Or a loading spinner
  }

  return (
    <div className={darkMode ? 'dark-mode' : 'light-mode'}>
      <Nav openAuthModal={openAuthModal} isAuthenticated={isAuthenticated} />
      <Routes>
        <Route path="/" element={<Home />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route 
          path="/profile" 
          element={
            <ProtectedRoute>
              <Profile />
            </ProtectedRoute>
          } 
        />
        <Route path="/forget" element={<Forgot />} />
        <Route path="/store" element={<Store />} />
        <Route path="/product/:id" element={
          <ProtectedRoute>
            <Product />
          </ProtectedRoute>
        } />
        <Route path="/confirmed" element={
          <ProtectedRoute>
            <Confirmed />
          </ProtectedRoute>
        } />
        <Route path='/checkout' element={
          <ProtectedRoute>
            <Checkout />
          </ProtectedRoute>
        } />
      </Routes>
    </div>
  );
};


export default Header;
