import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import axios from 'axios';

const API_URL = 'http://localhost:8000/api';

export default function PrintBill() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    axios.get(`${API_URL}/order.php?id=${id}`)
      .then(res => {
        if (res.data.success) {
          setData(res.data);
          // Wait a tiny bit for render, then trigger print
          setTimeout(() => {
            window.print();
          }, 300);
        } else {
          setError(res.data.message);
        }
      })
      .catch(err => setError('Failed to load bill data'));
  }, [id]);

  if (error) return <div className="p-8 text-center text-red-600 font-bold">{error}</div>;
  if (!data) return <div className="p-8 text-center text-gray-500">Loading Bill...</div>;

  const { order, items, shop } = data;
  
  const dateObj = new Date(order.created_at);
  const dateStr = dateObj.toLocaleDateString();
  const timeStr = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

  return (
    <div className="max-w-[380px] mx-auto p-5 bg-white text-black font-sans leading-relaxed">
      {/* Print styles applied dynamically or via generic CSS */}
      <style>
        {`
          @media print {
            body { margin: 0; padding: 0; background: #fff; }
            .no-print { display: none !important; }
          }
        `}
      </style>

      <div className="no-print mb-6 pb-6 border-b text-center">
        <button onClick={() => navigate('/pos')} className="bg-gray-200 text-black px-4 py-2 rounded-lg font-bold hover:bg-gray-300 mr-2">
          &larr; Back to POS
        </button>
        <button onClick={() => window.print()} className="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700">
          Print Again
        </button>
      </div>

      <div className="text-center mb-6">
        <h1 className="text-3xl font-extrabold lowercase mb-1 tracking-tight">{shop.name}</h1>
        <p className="text-sm text-gray-800 leading-tight mb-2">
          {shop.address.split(',').map((line, i) => <React.Fragment key={i}>{line}<br/></React.Fragment>)}
        </p>
        <p className="text-base font-bold">{shop.contact}</p>
      </div>

      <div className="flex justify-between border-b-2 border-dashed border-gray-300 pb-3 mb-3 text-sm font-bold">
        <span>Receipt: #{order.id}</span>
        <span>{dateStr} {timeStr}</span>
      </div>

      <div className="flex justify-between border-b-2 border-dashed border-gray-300 pb-3 mb-3 text-sm font-bold">
        <span>Payment Method</span>
        <span className="uppercase">
          {order.payment_method || 'Cash'}
          {order.payment_method === 'Card' && order.card_type ? ` (${order.card_type})` : ''}
        </span>
      </div>

      {order.payment_method === 'Card' && order.card_last_four && (
        <div className="flex justify-between border-b-2 border-dashed border-gray-300 pb-3 mb-3 text-sm">
          <span>Card Number</span>
          <span>**** **** **** {order.card_last_four}</span>
        </div>
      )}

      {(order.shop_name || order.contact_no) && (
        <div className="border-b-2 border-dashed border-gray-300 pb-3 mb-3 text-sm">
          {order.shop_name && <div><span className="font-bold">Customer:</span> {order.shop_name}</div>}
          {order.contact_no && <div><span className="font-bold">Contact:</span> {order.contact_no}</div>}
        </div>
      )}

      <table className="w-full text-sm mb-6">
        <thead>
          <tr className="border-b-2 border-black">
            <th className="py-2 text-left w-1/2">Item</th>
            <th className="py-2 text-center w-1/6">Qty</th>
            <th className="py-2 text-right w-1/3">Amount</th>
          </tr>
        </thead>
        <tbody className="border-b-2 border-black">
          {items.map(item => (
            <tr key={item.id} className="border-b border-gray-200 last:border-0">
              <td className="py-2 text-left pr-2">
                <div className="font-bold">{item.name}</div>
                {item.attribute && <div className="text-xs text-gray-500">{item.attribute}</div>}
              </td>
              <td className="py-2 text-center font-bold text-gray-700">{item.quantity}</td>
              <td className="py-2 text-right font-bold">
                {Number(item.price_at_purchase * item.quantity).toLocaleString(undefined, { minimumFractionDigits: 2 })}
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      <div className="flex justify-between items-center text-xl font-black border-b-2 border-dashed border-gray-300 pb-4 mb-6">
        <span>Total</span>
        <span>Rs. {Number(order.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
      </div>

      <div className="text-center text-sm font-bold mt-8">
        <p className="mb-2 uppercase tracking-wide">Thank you for your business!</p>
        <p className="text-gray-500">Please visit us again.</p>
      </div>

    </div>
  );
}
