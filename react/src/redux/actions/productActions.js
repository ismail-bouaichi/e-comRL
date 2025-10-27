import axios from 'axios';
import {
  FETCH_PRODUCTS_REQUEST,
  FETCH_PRODUCTS_SUCCESS,
  FETCH_PRODUCTS_FAILURE,
  SET_FAVORITE_PRODUCT_REQUEST,
  SET_FAVORITE_PRODUCT_SUCCESS,
  SET_FAVORITE_PRODUCT_FAILURE,
  FETCH_FAVORITE_PRODUCT_REQUEST,
  FETCH_FAVORITE_PRODUCT_SUCCESS,
  FETCH_FAVORITE_PRODUCT_FAILURE,
  SET_SELECTED_CATEGORY,
  FETCH_PRODUCT_REQUEST,
  FETCH_PRODUCT_SUCCESS,
  FETCH_PRODUCT_FAILURE,
  FETCH_BESTSELLING_PRODUCTS_FAILURE,
  FETCH_BESTSELLING_PRODUCTS_SUCCESS,
  FETCH_BESTSELLING_PRODUCTS_REQUEST,
} from '../constants/productConstants';

export const fetchProducts = (
  page = 1,
  sort = 'all',
  selectedCategories = [],
  selectedBrands = [],
  priceRange = { min: 0, max: 1000 },
  searchKey = ''
) => async (dispatch) => {
  dispatch({ type: FETCH_PRODUCTS_REQUEST });
  try {
    const response = await axios.get(`/product/list`, {
      params: {
        page,
        sort,
        selectedCategories: JSON.stringify(selectedCategories),
        selectedBrands: JSON.stringify(selectedBrands),
        min_price: priceRange.min,
        max_price: priceRange.max,
        searchKey,
      },
    });
    dispatch({ type: FETCH_PRODUCTS_SUCCESS, payload: response.data });
  } catch (error) {
    dispatch({ type: FETCH_PRODUCTS_FAILURE, payload: error.message });
  }
};



export const setFavoriteProduct = (productId) => async (dispatch) => {
  dispatch({ type: SET_FAVORITE_PRODUCT_REQUEST });
  try {
    await axios.post(`/product/${productId}/favorite`);
    dispatch({ type: SET_FAVORITE_PRODUCT_SUCCESS, payload: productId });
  } catch (error) {
    dispatch({ type: SET_FAVORITE_PRODUCT_FAILURE, payload: error.message });
  }
};



export const fetchFavoriteProducts = () => async (dispatch) => {
  dispatch({ type: FETCH_FAVORITE_PRODUCT_REQUEST });
  try {
    const response = await axios.get('/products/favorites');
    dispatch({ type: FETCH_FAVORITE_PRODUCT_SUCCESS, payload: response.data });
  } catch (error) {
    dispatch({ type: FETCH_FAVORITE_PRODUCT_FAILURE, payload: error.message });
  }
};

export const fetchProduct = (id) => async (dispatch) => {
  dispatch({ type: FETCH_PRODUCT_REQUEST });
  try {
    const response = await axios.get(`/product/${id}`);
    const { product, relatedByCategory, relatedByBrand } = response.data;
    dispatch({
      type: FETCH_PRODUCT_SUCCESS,
      payload: { product, relatedByCategory, relatedByBrand },
    });
  } catch (error) {
    dispatch({ type: FETCH_PRODUCT_FAILURE, error });
  }
};

export const setSelectedCategory = (category) => ({
  type: SET_SELECTED_CATEGORY,
  payload: category,
});


export const fetchBestSellingProducts = () => async (dispatch) => {
  dispatch({ type: FETCH_BESTSELLING_PRODUCTS_REQUEST });
  try {
    const response = await axios.get('/product/mostSelling');
    dispatch({ 
      type: FETCH_BESTSELLING_PRODUCTS_SUCCESS, 
      payload: response.data.data 
    });
  } catch (error) {
    dispatch({ 
      type: FETCH_BESTSELLING_PRODUCTS_FAILURE, 
      payload: error.message 
    });
  }
};