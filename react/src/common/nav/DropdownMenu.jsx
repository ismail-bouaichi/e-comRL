import React from 'react'
import { Link } from 'react-router-dom';

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
  

export default DropdownMenu
