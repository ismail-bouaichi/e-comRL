import React, { useEffect, useContext, useState, useMemo } from 'react';
import { Routes, Route, useLocation } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';

import Nav from './nav/Nav';
import Home from '../component/home/Home';
import Login from '../component/Login';
import Register from '../component/Register';
import Profile from '../component/profile/Profile';
import Forgot from '../component/Forgot';
import Store from '../component/store/Store';
import Product from '../component/Product';
import Success from '../component/Success';
import Confirmed from '../component/Confirmed';
import Checkout from '../component/checkout/Checkout';

import { ThemeContext } from '../theme/ThemeContext';
import { fetchUserData } from '../redux/actions/userActions';

import LoadingSpinner from '../components/LoadingSpinner'; // Import the loading spinner component

const Header = () => {
  const { darkMode } = useContext(ThemeContext);
  const dispatch = useDispatch();
  const [loading, setLoading] = useState(true);
  const [routeLoading, setRouteLoading] = useState(false); // State to manage route loading

  const user = useSelector((state) => state.user.user);
  const token = localStorage.getItem('token');
  const location = useLocation();

  useEffect(() => {
    if (token) {
      dispatch(fetchUserData()).finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, [dispatch, token]);

  useEffect(() => {
    setRouteLoading(true);
    const timeoutId = setTimeout(() => {
      setRouteLoading(false);
    }, 500); // Adjust the delay as needed

    return () => {
      clearTimeout(timeoutId);
    };
  }, [location]);

  const renderRoutes = useMemo(() => (
    <Routes>
      <Route path="/" element={<Home />} />
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />
      <Route path="/profile" element={<Profile user={user} />} />
      <Route path="/forget" element={<Forgot />} />
      <Route path="/store" element={<Store />} />
      <Route path="/product/:id" element={<Product />} />
      <Route path="/success" element={<Success />} />
      <Route path="/confirmed" element={<Confirmed />} />
      <Route path='/checkout' element={<Checkout />} />
    </Routes>
  ), [user]);

  if (loading) {
    return <LoadingSpinner darkMode={darkMode} />;
  }

  if (!token) {
    return <Login />;
  }

  return (
    <div className={darkMode ? 'dark-mode' : 'light-mode'}>
      <Nav />
      {routeLoading ? <LoadingSpinner darkMode={darkMode} /> : renderRoutes}
    </div>
  );
};

export default Header;
