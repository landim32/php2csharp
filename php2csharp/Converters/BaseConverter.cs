using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace PHP2CSharp.Converters
{
    public abstract class BaseConverter
    {
        public abstract string convert(string sourceCode);

        protected string getTrueType(string typeName)
        {
            if (typeName.StartsWith("null|", true, null))
            {
                typeName = typeName.Substring(5);
            }
            else if (typeName.EndsWith("|null", true, null))
            {
                typeName = typeName.Substring(0, typeName.Length - 5);
            }
            else if (typeName.EndsWith("[]", true, null))
            {
                typeName = "IList<" + typeName.Substring(0, typeName.Length - 2) + ">";
            }
            return typeName;
        }
    }
}
