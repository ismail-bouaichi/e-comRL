import React, { useState, useCallback, useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import { logoutUser } from '../redux/actions/userActions';
import ShoppingCart from './ShoppingCart';
import { FaSearch, FaUser, FaChevronDown, FaHome, FaBars } from 'react-icons/fa';
import { useTranslation } from 'react-i18next';
import {
  selectCategories,
  selectLoading as selectCategoriesLoading,
  selectError as selectCategoriesError,
} from '../redux/selectors/categorySelectors';
import {
  selectBrands,
  selectLoading as selectBrandsLoading,
  selectError as selectBrandsError,
} from '../redux/selectors/brandSelectors';

import { useDispatch, useSelector } from 'react-redux';

// Removed useDropdown hook

const DropdownMenu = ({ items }) => (
  <div
    className="invisible opacity-0 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 transition-all duration-200 absolute left-0 top-full w-64 bg-white border border-gray-200 rounded shadow-md z-50"
  >
    <div className="grid grid-cols-2 gap-4 p-4">
      {items.map((item, index) => (
        <Link
          key={index}
          to={item.path}
          className="text-gray-600 hover:bg-blue-50 hover:text-blue-600 text-sm p-2 rounded block text-center"
        >
          {item.name}
        </Link>
      ))}
    </div>
  </div>
);


const Nav = ({ openAuthModal }) => {
  const { t, i18n } = useTranslation();

  const ROUTES = {
    HOME: '/',
    STORE: '/store',
    PROFILE: '/profile',
  };

  const links = [
    { name: t('Home'), path: ROUTES.HOME },
    { name: t('Store'), path: ROUTES.STORE },
  ];

  const [isOpen, setIsOpen] = useState(false);
  const [isLangDropdownOpen, setIsLangDropdownOpen] = useState(false);
  const [isTransitionEnded, setIsTransitionEnded] = useState(false);

  const categories = useSelector(selectCategories) || [];
  const brands = useSelector(selectBrands) || [];

  const navRef = useRef();

  const dispatch = useDispatch();

  const toggleMenu = useCallback(() => {
    setIsOpen(!isOpen);
    setIsTransitionEnded(false);
  }, [isOpen]);

  const handleTransitionEnd = useCallback(() => {
    setIsTransitionEnded(true);
  }, []);

  useEffect(() => {
    if (!isOpen) {
      setIsTransitionEnded(false);
    }
  }, [isOpen]);

  const handleLogout = useCallback(() => {
    dispatch(logoutUser());
  }, [dispatch]);

  const changeLanguage = useCallback((lng) => {
    i18n.changeLanguage(lng);
    setIsLangDropdownOpen(false);
  }, [i18n]);

  const isLoggedIn = !!localStorage.getItem('token');

  const profile = isLoggedIn ? (
    <>
      <Link className="text-gray-600 hover:text-gray-800" to={ROUTES.PROFILE}>
        {t('Profile')}
      </Link>
      <Link className="text-gray-600 hover:text-gray-800" onClick={handleLogout}>
        {t('Logout')}
      </Link>
    </>
  ) : (
    <button onClick={openAuthModal}>{t('Sign In / Register')}</button>
  );

  // Mobile dropdown state remains
  const [isMobileDropdownOpen, setIsMobileDropdownOpen] = useState({
    men: false,
    women: false,
    pages: false,
  });

  const toggleMobileDropdown = (dropdown) => {
    setIsMobileDropdownOpen((prevState) => ({
      ...prevState,
      [dropdown]: !prevState[dropdown],
    }));
  };

  return (
    <header className="sticky top-0 w-full z-50 bg-white shadow-md" ref={navRef}>
      <nav className="bg-white shadow-md">
        <div className="container mx-auto px-4 py-0 max-650:py-4 flex items-center justify-between">
          <Link to={ROUTES.HOME} className="text-2xl font-bold">Ismail Bouaichi</Link>

          {/* Desktop Navigation */}
          <div className="hidden lg:flex space-x-6">
            {/* Categories Dropdown */}
         {/* Categories Dropdown */}
<div className="group relative cursor-pointer p-4">
  <span className="flex items-center z-50">
    {t('Categories')}
    <FaChevronDown className="ml-1 text-xs" />
  </span>
  <DropdownMenu
    items={categories.map((category) => ({
      name: category.name,
      path: `/category/${category.slug}`,
    }))}
  />
</div>

{/* Brands Dropdown */}
<div className="group relative cursor-pointer p-4">
  <span className="flex items-center z-50">
    {t('Brands')}
    <FaChevronDown className="ml-1 text-xs" />
  </span>
  <DropdownMenu
    items={brands.map((brand) => ({
      name: brand.name,
      path: `/brand/${brand.slug}`,
    }))}
  />
</div>

{/* Pages Dropdown */}
<div className="group relative cursor-pointer p-4">
  <span className="flex items-center">
    {t('Pages')}
    <FaChevronDown className="ml-1 text-xs" />
  </span>
  <DropdownMenu
    items={[
      { name: t('About Us'), path: '/about' },
      { name: t('Contact Us'), path: '/contact' },
      { name: t('FAQs'), path: '/faqs' },
    ]}
  />
</div>

            {/* Store Link */}
            <Link to={ROUTES.STORE} className="text-gray-600 hover:text-gray-800 p-4">
              {t('Store')}
            </Link>
          </div>

          {/* Right Side Icons */}
          <div className="flex items-center space-x-4">
            <div className="relative">
              <button
                onClick={() => setIsLangDropdownOpen(!isLangDropdownOpen)}
                className="flex items-center focus:outline-none"
              >
                <span className="mr-2">{i18n.language === 'en' ? 'English' : 'French'}</span>
                <FaChevronDown className="text-xs" />
              </button>
              {isLangDropdownOpen && (
                <div className="absolute right-0 mt-2 w-32 bg-white border rounded shadow-lg">
                  <button
                    onClick={() => changeLanguage('en')}
                    className="block w-full text-left px-4 py-2 text-gray-600 hover:bg-gray-100"
                  >
                    English
                  </button>
                  <button
                    onClick={() => changeLanguage('fr')}
                    className="block w-full text-left px-4 py-2 text-gray-600 hover:bg-gray-100"
                  >
                    French
                  </button>
                </div>
              )}
            </div>
            {profile}
            <div className="xxs:hidden">
              <ShoppingCart />
            </div>
          </div>

          {/* Mobile Menu Toggle */}
          <div className="lg:hidden">
            <button className="navbar-burger flex items-center text-blue-600 p-3" onClick={toggleMenu}>
              <FaBars size={20} />
            </button>
          </div>
        </div>
      </nav>

    
      <div 
        className={`navbar-menu transition-transform duration-500 ease-in-out fixed top-0 left-0 bottom-0 flex flex-col w-full py-6 px-6 overflow-y-auto z-50 ${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        }`} 
        onTransitionEnd={handleTransitionEnd}
      >
        <div 
          className={`navbar-backdrop fixed inset-0 ${
            isTransitionEnded && isOpen ? 'backdrop-blur-lg' : ''
          } ${isOpen ? 'block' : 'hidden'}`} 
          onClick={toggleMenu}
        ></div>
        <nav className="fixed top-0 left-0 bottom-0 flex flex-col w-5/6 max-w-sm py-6 px-6 bg-white border-r overflow-y-auto">
          <div className="flex items-center mb-8">
            <a className="mr-auto text-3xl font-bold leading-none" href="#">
              LOGO
            </a>
            <button className="navbar-close" onClick={toggleMenu}>
              <svg className="h-6 w-6 text-gray-400 cursor-pointer hover:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          <div>
          <ul>
            {links.map((link, index) => (
              <li key={index} className="mb-1">
                <Link
                  to={link.path}
                  className="block p-4 text-sm font-semibold text-gray-400 hover:bg-blue-50 hover:text-blue-600 rounded"
                  onClick={toggleMenu}
                >
                  {link.name}
                </Link>
              </li>
            ))}

        {/* Men Wear Dropdown */}
        <li className="mb-2">
        <button
            onClick={() => toggleMobileDropdown('men')}
            className="flex items-center justify-between w-full p-4 text-sm font-semibold text-gray-400 hover:bg-blue-50 hover:text-blue-600 rounded"
          >
            <span>{t('Men Wear')}</span>
            <FaChevronDown
              className={`ml-2 transform transition-transform ${
                isMobileDropdownOpen.men ? 'rotate-180' : ''
              }`}
            />
          </button>
          {isMobileDropdownOpen.men && (
            <ul className="mt-2">
              {[
                { name: t('T-Shirt'), path: '/category/men/t-shirts' },
                { name: t('Casual Shirts'), path: '/category/men/casual-shirts' },
                { name: t('Formal Shirts'), path: '/category/men/formal-shirts' },
                { name: t('Blazers & Coats'), path: '/category/men/blazers-coats' },
                { name: t('Suits'), path: '/category/men/suits' },
                { name: t('Jackets'), path: '/category/men/jackets' },
              ].map((item, index) => (
                <li key={index} className="mb-1">
                  <Link
                    to={item.path}
                    className="block pl-8 p-4 text-sm font-semibold text-gray-400 hover:bg-blue-50 hover:text-blue-600 rounded"
                    onClick={toggleMenu}
                  >
                    {item.name}
                  </Link>
                </li>
              ))}
            </ul>
          )}
        </li>

          {/* Women Wear Dropdown */}
          <li className="mb-2">
          <button
              onClick={() => toggleMobileDropdown('women')}
              className="flex items-center justify-between w-full p-4 text-sm font-semibold text-gray-400 hover:bg-blue-50 hover:text-blue-600 rounded"
            >
              <span>{t('Women Wear')}</span>
              <FaChevronDown
                className={`ml-2 transform transition-transform ${
                  isMobileDropdownOpen.women ? 'rotate-180' : ''
                }`}
              />
            </button>
          
            {isMobileDropdownOpen.women && (
              <ul className="mt-2">
                {[
                  { name: t('Dresses'), path: '/category/women/dresses' },
                  { name: t('Jumpsuits'), path: '/category/women/jumpsuits' },
                  { name: t('Tops, T-Shirts & Shirts'), path: '/category/women/tops-tshirts-shirts' },
                  { name: t('Shorts & Skirts'), path: '/category/women/shorts-skirts' },
                  { name: t('Shrugs'), path: '/category/women/shrugs' },
                  { name: t('Blazers'), path: '/category/women/blazers' },
                ].map((item, index) => (
                  <li key={index} className="mb-1">
                    <Link
                      to={item.path}
                      className="block pl-8 p-4 text-sm font-semibold text-gray-400 hover:bg-blue-50 hover:text-blue-600 rounded"
                      onClick={toggleMenu}
                    >
                      {item.name}
                    </Link>
                  </li>
                ))}
              </ul>
            )}
          </li>

  {/* Pages Dropdown */}
        <li className="mb-2">
        <button
            onClick={() => toggleMobileDropdown('pages')}
            className="flex items-center justify-between w-full p-4 text-sm font-semibold text-gray-400 hover:bg-blue-50 hover:text-blue-600 rounded"
          >
            <span>{t('Pages')}</span>
            <FaChevronDown
              className={`ml-2 transform transition-transform ${
                isMobileDropdownOpen.pages ? 'rotate-180' : ''
              }`}
            />
          </button>
          {isMobileDropdownOpen.pages && (
            <ul className="mt-2">
              {[
                { name: t('About Us'), path: '/about' },
                { name: t('Contact Us'), path: '/contact' },
                { name: t('FAQs'), path: '/faqs' },
              ].map((item, index) => (
                <li key={index} className="mb-1">
                  <Link
                    to={item.path}
                    className="block pl-8 p-4 text-sm font-semibold text-gray-400 hover:bg-blue-50 hover:text-blue-600 rounded"
                    onClick={toggleMenu}
                  >
                    {item.name}
                  </Link>
                </li>
              ))}
            </ul>
          )}
        </li>
      </ul>
          </div>
          <div className="mt-auto">
            <div className="pt-6">
              <Link to="/login" className="block px-4 py-3 mb-3 leading-loose text-xs text-center font-semibold  bg-gray-50 hover:bg-gray-100 rounded-xl">
                {t('Sign In')}
              </Link>
              <Link to="/register" className="block px-4 py-3 mb-2 leading-loose text-xs text-center text-white font-semibold bg-blue-600 hover:bg-blue-700 rounded-xl">
                {t('Sign Up')}
              </Link>
            </div>
            <p className="my-4 text-xs text-center text-gray-400">
              <span>Copyright © 2023</span>
            </p>
          </div>
        </nav>
      </div>

      <div className="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t flex justify-around items-center h-16 z-40">
        <button onClick={toggleMenu}><FaBars size={20} /></button>
        <Link to="/search"><FaSearch size={20} /></Link>
        <Link to={ROUTES.HOME}><FaHome size={20} /></Link>
        <ShoppingCart />
        <Link to={ROUTES.PROFILE}><FaUser size={20} /></Link>
      </div>
    </header>
  );
};

export default React.memo(Nav);
